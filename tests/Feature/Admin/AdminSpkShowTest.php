<?php

namespace Tests\Feature\Admin;

use App\Enums\StatusRambuPasang;
use App\Enums\StatusSpk;
use App\Enums\Urgensi;
use App\Livewire\Admin\Spk\Edit as SpkEditComponent;
use App\Livewire\Admin\Spk\Riwayat;
use App\Livewire\Admin\Spk\Show as SpkShowComponent;
use App\Models\DikerjakanOleh;
use App\Models\JenisRambu;
use App\Models\Kendala;
use App\Models\Notifikasi;
use App\Models\Rambu;
use App\Models\RambuPasang;
use App\Models\RtPerwakilan;
use App\Models\Spk;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminSpkShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_petugas_cannot_view_admin_spk_detail(): void
    {
        $this->actingAs(User::factory()->create());

        $spk = Spk::create([
            'nomor_surat' => 'SR-2026/BJM/7001',
            'dibuat_oleh' => User::factory()->admin()->create()->id,
            'jenis_spk' => 'pasang_baru',
            'wilayah' => 'Banjarmasin Tengah',
            'deadline' => now()->addDays(5),
            'urgensi' => 'sedang',
            'status' => 'aktif',
            'asal_permintaan' => 'internal',
        ]);

        $this->get(route('admin.spk.show', $spk))->assertRedirect(route('dashboard'));
    }

    public function test_admin_can_view_spk_detail_with_rambu_list(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $jenis = JenisRambu::create(['nama_jenis' => 'Rambu Peringatan']);
        $rambu = Rambu::create([
            'jenis_rambu_id' => $jenis->id,
            'wilayah' => 'Banjarmasin Tengah',
            'lokasi' => 'Depan pasar lama',
            'koordinat' => '-3.30,114.59',
        ]);

        $spk = Spk::create([
            'nomor_surat' => 'SR-2026/BJM/7002',
            'dibuat_oleh' => $admin->id,
            'jenis_spk' => 'pasang_baru',
            'wilayah' => 'Banjarmasin Tengah',
            'deadline' => now()->addDays(5),
            'urgensi' => 'sedang',
            'status' => 'aktif',
            'asal_permintaan' => 'internal',
        ]);

        RambuPasang::create([
            'rambu_spk_id' => $spk->id,
            'rambu_id' => $rambu->id,
            'jenis_pekerjaan' => 'pasang_baru',
            'jumlah' => 1,
            'status' => 'belum',
        ]);

        $response = $this->get(route('admin.spk.show', $spk));

        $response->assertOk();
        $response->assertSee('SR-2026/BJM/7002');
        $response->assertSee('Depan pasar lama');
    }

    public function test_admin_spk_detail_rambu_card_shows_foto_koordinat_and_map_links(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $jenis = JenisRambu::create(['nama_jenis' => 'Rambu Peringatan']);
        $rambu = Rambu::create([
            'jenis_rambu_id' => $jenis->id,
            'wilayah' => 'Banjarmasin Tengah',
            'lokasi' => 'Depan pasar lama',
            'koordinat' => '-3.30,114.59',
        ]);

        $spk = Spk::create([
            'nomor_surat' => 'SR-2026/BJM/7006',
            'dibuat_oleh' => $admin->id,
            'jenis_spk' => 'pasang_baru',
            'wilayah' => 'Banjarmasin Tengah',
            'deadline' => now()->addDays(5),
            'urgensi' => 'sedang',
            'status' => 'aktif',
            'asal_permintaan' => 'internal',
        ]);

        $rambuPasang = RambuPasang::create([
            'rambu_spk_id' => $spk->id,
            'rambu_id' => $rambu->id,
            'jenis_pekerjaan' => 'pasang_baru',
            'jumlah' => 1,
            'status' => 'belum',
            'foto_survei' => 'rambu-pasang/survei/contoh-detail-spk.jpg',
        ]);

        $response = $this->get(route('admin.spk.show', $spk));

        $response->assertOk();
        $response->assertSee('rambu-pasang/survei/contoh-detail-spk.jpg', false);
        $response->assertSee('-3.30,114.59');
        $response->assertSee(route('peta', ['focus' => $rambuPasang->rambu_id]), false);
        $response->assertSee('https://www.google.com/maps/search/?api=1&query=-3.30,114.59');
    }

    public function test_admin_spk_detail_shows_tanggal_dan_petugas_survei(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $spk = Spk::create([
            'nomor_surat' => 'SR-2026/BJM/7007',
            'dibuat_oleh' => $admin->id,
            'jenis_spk' => 'pasang_baru',
            'wilayah' => 'Banjarmasin Tengah',
            'deadline' => now()->addDays(5),
            'urgensi' => 'sedang',
            'status' => 'aktif',
            'asal_permintaan' => 'internal',
            'tanggal_survei' => '2026-06-15',
            'petugas_survei' => 'Budi, Andi',
        ]);

        $response = $this->get(route('admin.spk.show', $spk));

        $response->assertOk();
        $response->assertSee('Budi, Andi');
    }

    public function test_daftar_surat_card_shows_placeholder_not_jenis_rambu_icon_when_no_foto_survei(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $jenis = JenisRambu::create([
            'nama_jenis' => 'Rambu Larangan',
            'gambar_referensi' => 'jenis-rambu/rambu-larangan.svg',
        ]);

        $rambu = Rambu::create([
            'jenis_rambu_id' => $jenis->id,
            'wilayah' => 'Banjarmasin Tengah',
            'lokasi' => 'Depan pasar lama',
            'koordinat' => '-3.30,114.59',
        ]);

        $spk = Spk::create([
            'nomor_surat' => 'SR-2026/BJM/7004',
            'dibuat_oleh' => $admin->id,
            'jenis_spk' => 'pasang_baru',
            'wilayah' => 'Banjarmasin Tengah',
            'deadline' => now()->addDays(5),
            'urgensi' => 'sedang',
            'status' => 'aktif',
            'asal_permintaan' => 'internal',
        ]);

        RambuPasang::create([
            'rambu_spk_id' => $spk->id,
            'rambu_id' => $rambu->id,
            'jenis_pekerjaan' => 'pasang_baru',
            'jumlah' => 1,
            'status' => 'belum',
        ]);

        $response = $this->get(route('admin.spk.index'));

        $response->assertOk();
        $response->assertDontSee('jenis-rambu/rambu-larangan.svg', false);
    }

    public function test_daftar_surat_card_shows_foto_survei_as_cover_when_available(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $jenis = JenisRambu::create(['nama_jenis' => 'Rambu Larangan']);

        $rambu = Rambu::create([
            'jenis_rambu_id' => $jenis->id,
            'wilayah' => 'Banjarmasin Tengah',
            'lokasi' => 'Depan pasar lama',
            'koordinat' => '-3.30,114.59',
        ]);

        $spk = Spk::create([
            'nomor_surat' => 'SR-2026/BJM/7005',
            'dibuat_oleh' => $admin->id,
            'jenis_spk' => 'pasang_baru',
            'wilayah' => 'Banjarmasin Tengah',
            'deadline' => now()->addDays(5),
            'urgensi' => 'sedang',
            'status' => 'aktif',
            'asal_permintaan' => 'internal',
        ]);

        RambuPasang::create([
            'rambu_spk_id' => $spk->id,
            'rambu_id' => $rambu->id,
            'jenis_pekerjaan' => 'pasang_baru',
            'jumlah' => 1,
            'status' => 'belum',
            'foto_survei' => 'rambu-pasang/survei/contoh-daftar-surat.jpg',
        ]);

        $response = $this->get(route('admin.spk.index'));

        $response->assertOk();
        $response->assertSee('rambu-pasang/survei/contoh-daftar-surat.jpg', false);
    }

    public function test_daftar_surat_lihat_detail_link_points_to_admin_spk_show(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $spk = Spk::create([
            'nomor_surat' => 'SR-2026/BJM/7003',
            'dibuat_oleh' => $admin->id,
            'jenis_spk' => 'pasang_baru',
            'wilayah' => 'Banjarmasin Tengah',
            'deadline' => now()->addDays(5),
            'urgensi' => 'sedang',
            'status' => 'aktif',
            'asal_permintaan' => 'internal',
        ]);

        $response = $this->get(route('admin.spk.index'));

        $response->assertOk();
        $response->assertSee(route('admin.spk.show', $spk), false);
    }

    public function test_daftar_surat_only_shows_aktif_spk(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        Spk::create([
            'nomor_surat' => 'SR-2026/BJM/8010',
            'dibuat_oleh' => $admin->id,
            'jenis_spk' => 'pasang_baru',
            'wilayah' => 'Banjarmasin Tengah',
            'deadline' => now()->addDays(5),
            'urgensi' => 'sedang',
            'status' => 'selesai',
            'asal_permintaan' => 'internal',
        ]);

        Spk::create([
            'nomor_surat' => 'SR-2026/BJM/8014',
            'dibuat_oleh' => $admin->id,
            'jenis_spk' => 'pasang_baru',
            'wilayah' => 'Banjarmasin Tengah',
            'deadline' => now()->addDays(5),
            'urgensi' => 'sedang',
            'status' => 'dibatalkan',
            'asal_permintaan' => 'internal',
        ]);

        $this->get(route('admin.spk.index'))
            ->assertDontSee('SR-2026/BJM/8010')
            ->assertDontSee('SR-2026/BJM/8014');
    }

    public function test_riwayat_spk_shows_selesai_and_dibatalkan_filterable_by_status(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        Spk::create([
            'nomor_surat' => 'SR-2026/BJM/8015',
            'dibuat_oleh' => $admin->id,
            'jenis_spk' => 'pasang_baru',
            'wilayah' => 'Banjarmasin Tengah',
            'deadline' => now()->addDays(5),
            'urgensi' => 'sedang',
            'status' => 'selesai',
            'asal_permintaan' => 'internal',
        ]);

        Spk::create([
            'nomor_surat' => 'SR-2026/BJM/8016',
            'dibuat_oleh' => $admin->id,
            'jenis_spk' => 'pasang_baru',
            'wilayah' => 'Banjarmasin Tengah',
            'deadline' => now()->addDays(5),
            'urgensi' => 'sedang',
            'status' => 'dibatalkan',
            'asal_permintaan' => 'internal',
        ]);

        $this->get(route('admin.spk.riwayat'))
            ->assertSee('SR-2026/BJM/8015')
            ->assertSee('SR-2026/BJM/8016');

        Livewire::test(Riwayat::class)
            ->set('status', 'selesai')
            ->assertSee('SR-2026/BJM/8015')
            ->assertDontSee('SR-2026/BJM/8016');
    }

    public function test_riwayat_spk_card_shows_durasi_and_selisih_deadline_for_selesai(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $spk = Spk::create([
            'nomor_surat' => 'SR-2026/BJM/8017',
            'dibuat_oleh' => $admin->id,
            'jenis_spk' => 'pasang_baru',
            'wilayah' => 'Banjarmasin Tengah',
            'deadline' => now()->addDays(10),
            'urgensi' => 'sedang',
            'status' => 'selesai',
            'asal_permintaan' => 'internal',
        ]);
        $spk->created_at = now()->subDays(12);
        $spk->selesai_pada = now()->subDays(2);
        $spk->save();

        $this->get(route('admin.spk.riwayat'))
            ->assertSee('Durasi 10 hari')
            ->assertSee('12 hari lebih cepat dari deadline')
            ->assertSee('bg-green-400', false);
    }

    // Was plain gray text (no color at all) until this was made to match
    // Detail Surat's red/green badge treatment — an overdue SPK didn't stand
    // out from an on-time one when scanning a page full of Riwayat cards.
    public function test_riwayat_spk_card_shows_red_badge_when_terlambat(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $spk = Spk::create([
            'nomor_surat' => 'SR-2026/BJM/8019',
            'dibuat_oleh' => $admin->id,
            'jenis_spk' => 'pasang_baru',
            'wilayah' => 'Banjarmasin Tengah',
            'deadline' => now()->subDays(5),
            'urgensi' => 'rendah',
            'status' => 'selesai',
            'asal_permintaan' => 'internal',
        ]);
        $spk->created_at = now()->subDays(20);
        $spk->selesai_pada = now()->subDays(2);
        $spk->save();

        $this->get(route('admin.spk.riwayat'))
            ->assertSee('Terlambat 3 hari dari deadline')
            ->assertSee('bg-red-400', false);
    }

    public function test_riwayat_spk_card_does_not_show_durasi_for_dibatalkan(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        Spk::create([
            'nomor_surat' => 'SR-2026/BJM/8018',
            'dibuat_oleh' => $admin->id,
            'jenis_spk' => 'pasang_baru',
            'wilayah' => 'Banjarmasin Tengah',
            'deadline' => now()->addDays(10),
            'urgensi' => 'sedang',
            'status' => 'dibatalkan',
            'asal_permintaan' => 'internal',
        ]);

        $this->get(route('admin.spk.riwayat'))
            ->assertDontSee('Durasi');
    }

    public function test_admin_can_edit_spk_details(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $spk = Spk::create([
            'nomor_surat' => 'SR-2026/BJM/8011',
            'dibuat_oleh' => $admin->id,
            'jenis_spk' => 'pasang_baru',
            'jalan' => 'Veteran',
            'rt' => '5',
            'kelurahan' => 'Antasan Besar',
            'wilayah' => 'Jl. Veteran RT. 5 Kel. Antasan Besar',
            'deadline' => now()->addDays(10),
            'prioritas' => false,
            'urgensi' => 'rendah',
            'status' => 'aktif',
            'asal_permintaan' => 'internal',
        ]);

        $jenis = JenisRambu::create(['nama_jenis' => 'Rambu Peringatan']);
        $rambu = Rambu::create([
            'jenis_rambu_id' => $jenis->id,
            'wilayah' => 'Jl. Veteran RT. 5 Kel. Antasan Besar',
            'lokasi' => 'Depan kantor lurah',
            'koordinat' => '-3.30,114.59',
        ]);
        RambuPasang::create([
            'rambu_spk_id' => $spk->id,
            'rambu_id' => $rambu->id,
            'jenis_pekerjaan' => 'pasang_baru',
            'jumlah' => 1,
            'status' => 'belum',
        ]);

        Livewire::test(SpkEditComponent::class, ['spk' => $spk])
            ->set('jalan', 'Ahmad Yani')
            ->set('rt', '12')
            ->set('kelurahan', 'Kertak Baru')
            ->set('perihal', 'perbaikan rambu larangan')
            ->set('deadline', now()->addDays(1)->toDateString())
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('admin.spk.show', $spk));

        $spk->refresh();

        $this->assertSame('Jl. Ahmad Yani RT. 12 Kel. Kertak Baru', $spk->wilayah);
        $this->assertSame('perbaikan rambu larangan', $spk->perihal);
        $this->assertSame(Urgensi::Tinggi, $spk->urgensi);
    }

    public function test_admin_can_edit_tanggal_dan_petugas_survei(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $spk = Spk::create([
            'nomor_surat' => 'SR-2026/BJM/8017',
            'dibuat_oleh' => $admin->id,
            'jenis_spk' => 'pasang_baru',
            'jalan' => 'Veteran',
            'rt' => '5',
            'kelurahan' => 'Antasan Besar',
            'wilayah' => 'Jl. Veteran RT. 5 Kel. Antasan Besar',
            'deadline' => now()->addDays(10),
            'urgensi' => 'rendah',
            'status' => 'aktif',
            'asal_permintaan' => 'internal',
        ]);

        $jenis = JenisRambu::create(['nama_jenis' => 'Rambu Peringatan']);
        $rambu = Rambu::create([
            'jenis_rambu_id' => $jenis->id,
            'wilayah' => 'Jl. Veteran RT. 5 Kel. Antasan Besar',
            'lokasi' => 'Depan kantor lurah',
            'koordinat' => '-3.30,114.59',
        ]);
        RambuPasang::create([
            'rambu_spk_id' => $spk->id,
            'rambu_id' => $rambu->id,
            'jenis_pekerjaan' => 'pasang_baru',
            'jumlah' => 1,
            'status' => 'belum',
        ]);

        Livewire::test(SpkEditComponent::class, ['spk' => $spk])
            ->set('tanggal_survei', '2026-06-15')
            ->set('petugas_survei', '')
            ->call('save')
            ->assertHasErrors(['petugas_survei' => 'required_with']);

        Livewire::test(SpkEditComponent::class, ['spk' => $spk])
            ->set('tanggal_survei', '2026-06-15')
            ->set('petugas_survei', 'Budi, Andi')
            ->call('save')
            ->assertHasNoErrors();

        $spk->refresh();

        $this->assertSame('2026-06-15', $spk->tanggal_survei->toDateString());
        $this->assertSame('Budi, Andi', $spk->petugas_survei);
    }

    public function test_cannot_edit_selesai_spk(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $spk = Spk::create([
            'nomor_surat' => 'SR-2026/BJM/8012',
            'dibuat_oleh' => $admin->id,
            'jenis_spk' => 'pasang_baru',
            'wilayah' => 'Banjarmasin Tengah',
            'deadline' => now()->addDays(5),
            'urgensi' => 'sedang',
            'status' => 'selesai',
            'asal_permintaan' => 'internal',
        ]);

        $this->get(route('admin.spk.edit', $spk))->assertForbidden();
    }

    public function test_admin_spk_detail_shows_durasi_pengerjaan_dan_selisih_deadline_when_selesai(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $spk = Spk::create([
            'nomor_surat' => 'SR-2026/BJM/7008',
            'dibuat_oleh' => $admin->id,
            'jenis_spk' => 'pasang_baru',
            'wilayah' => 'Banjarmasin Tengah',
            'deadline' => now()->addDays(10),
            'urgensi' => 'sedang',
            'status' => 'selesai',
            'asal_permintaan' => 'internal',
        ]);

        // Backdate created_at (Eloquent only auto-sets it on insert, not on
        // later saves) so the duration math is deterministic: created 12
        // days ago, finished 2 days ago -> 10 hari duration, 2 hari earlier
        // than the deadline set above (which is 10 days from now).
        $spk->created_at = now()->subDays(12);
        $spk->selesai_pada = now()->subDays(2);
        $spk->save();

        $this->assertSame(10, $spk->durasiPengerjaanHari());
        $this->assertSame(-12, $spk->selisihDeadlineHari());
        $this->assertSame('12 hari lebih cepat dari deadline', $spk->selisihDeadlineLabel());

        $response = $this->get(route('admin.spk.show', $spk));

        $response->assertOk();
        $response->assertSee('Durasi Pengerjaan');
        $response->assertSee('10 hari');
        $response->assertSee('12 hari lebih cepat dari deadline');
    }

    public function test_urgensi_saat_ini_recomputes_live_for_aktif_spk_with_a_stale_stored_value(): void
    {
        $admin = User::factory()->admin()->create();

        // Stored value is stale on purpose: it was correct back when the SPK
        // was created (deadline far out -> rendah), but the deadline has
        // since passed and nothing has re-saved the record since, exactly
        // what happens to seeded/long-untouched data.
        $spk = Spk::create([
            'nomor_surat' => 'SR-2026/BJM/7020',
            'dibuat_oleh' => $admin->id,
            'jenis_spk' => 'pasang_baru',
            'wilayah' => 'Banjarmasin Tengah',
            'deadline' => now()->subDays(3),
            'urgensi' => 'rendah',
            'status' => 'aktif',
            'asal_permintaan' => 'internal',
        ]);

        $this->assertSame('rendah', $spk->urgensi->value);
        $this->assertSame(Urgensi::Tinggi, $spk->urgensiSaatIni());
    }

    public function test_urgensi_saat_ini_keeps_the_frozen_stored_value_for_selesai_spk(): void
    {
        $admin = User::factory()->admin()->create();

        $spk = Spk::create([
            'nomor_surat' => 'SR-2026/BJM/7021',
            'dibuat_oleh' => $admin->id,
            'jenis_spk' => 'pasang_baru',
            'wilayah' => 'Banjarmasin Tengah',
            'deadline' => now()->subDays(30),
            'urgensi' => 'rendah',
            'status' => 'selesai',
            'asal_permintaan' => 'internal',
        ]);

        // Live recomputation against today's date would say Tinggi (deadline
        // long past), but this SPK is done — its urgensi is a historical
        // fact now, not something that should keep drifting after the fact.
        $this->assertSame(Urgensi::Rendah, $spk->urgensiSaatIni());
    }

    public function test_daftar_surat_badge_reflects_live_urgensi_not_the_stale_stored_value(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        Spk::create([
            'nomor_surat' => 'SR-2026/BJM/7022',
            'dibuat_oleh' => $admin->id,
            'jenis_spk' => 'pasang_baru',
            'wilayah' => 'Banjarmasin Tengah',
            'deadline' => now()->subDays(3),
            'urgensi' => 'rendah',
            'status' => 'aktif',
            'asal_permintaan' => 'internal',
        ]);

        $response = $this->get(route('admin.spk.index'));

        $response->assertOk();
        $response->assertSee('Tinggi');
        $response->assertDontSee('Rendah');
    }

    public function test_admin_spk_detail_shows_contact_person_when_present(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $spk = Spk::create([
            'nomor_surat' => 'SR-2026/BJM/7009',
            'dibuat_oleh' => $admin->id,
            'jenis_spk' => 'pasang_baru',
            'wilayah' => 'Banjarmasin Tengah',
            'deadline' => now()->addDays(5),
            'urgensi' => 'sedang',
            'status' => 'aktif',
            'asal_permintaan' => 'internal',
        ]);

        RtPerwakilan::create([
            'nama_lengkap' => 'Abdul',
            'no_telepon' => '08226735526',
            'rtperwakilan_spk_id' => $spk->id,
        ]);

        $response = $this->get(route('admin.spk.show', $spk));

        $response->assertOk();
        $response->assertSee('Contact Person');
        $response->assertSee('Abdul (08226735526)');
    }

    public function test_admin_spk_detail_shows_kendala_reason_for_tertunda_rambu(): void
    {
        $admin = User::factory()->admin()->create();
        $petugas = User::factory()->create();
        $this->actingAs($admin);

        $spk = Spk::create([
            'nomor_surat' => 'SR-2026/BJM/7010',
            'dibuat_oleh' => $admin->id,
            'jenis_spk' => 'pasang_baru',
            'wilayah' => 'Banjarmasin Tengah',
            'deadline' => now()->addDays(5),
            'urgensi' => 'sedang',
            'status' => 'aktif',
            'asal_permintaan' => 'internal',
        ]);

        $jenis = JenisRambu::create(['nama_jenis' => 'Rambu Peringatan']);
        $rambu = Rambu::create([
            'jenis_rambu_id' => $jenis->id,
            'wilayah' => 'Banjarmasin Tengah',
            'lokasi' => 'Depan kantor lurah',
            'koordinat' => '-3.30,114.59',
        ]);
        $rp = RambuPasang::create([
            'rambu_spk_id' => $spk->id,
            'rambu_id' => $rambu->id,
            'jenis_pekerjaan' => 'pasang_baru',
            'jumlah' => 1,
            'status' => 'tertunda',
        ]);

        Kendala::create([
            'rambu_pasang_id' => $rp->id,
            'dilaporkan_oleh' => $petugas->id,
            'alasan' => 'Warga menolak, minta dipindah ke gang sebelah.',
            'foto' => 'kendala/fake.jpg',
        ]);

        $response = $this->get(route('admin.spk.show', $spk));

        $response->assertOk();
        $response->assertSee('Kendala yang dilaporkan');
        $response->assertSee('Warga menolak, minta dipindah ke gang sebelah.');
    }

    public function test_admin_can_batalkan_spk(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $jenis = JenisRambu::create(['nama_jenis' => 'Rambu Peringatan']);
        $rambu = Rambu::create([
            'jenis_rambu_id' => $jenis->id,
            'wilayah' => 'Banjarmasin Tengah',
            'lokasi' => 'Depan pasar lama',
            'koordinat' => '-3.30,114.59',
        ]);

        $spk = Spk::create([
            'nomor_surat' => 'SR-2026/BJM/8013',
            'dibuat_oleh' => $admin->id,
            'jenis_spk' => 'pasang_baru',
            'wilayah' => 'Banjarmasin Tengah',
            'deadline' => now()->addDays(5),
            'urgensi' => 'sedang',
            'status' => 'aktif',
            'asal_permintaan' => 'internal',
        ]);

        $rambuPasang = RambuPasang::create([
            'rambu_spk_id' => $spk->id,
            'rambu_id' => $rambu->id,
            'jenis_pekerjaan' => 'pasang_baru',
            'jumlah' => 1,
            'status' => 'belum',
        ]);

        $petugas = User::factory()->create();
        DikerjakanOleh::create([
            'by_spk_id' => $spk->id,
            'by_user_id' => $petugas->id,
            'is_perwakilan' => true,
        ]);

        Livewire::test(SpkShowComponent::class, ['spk' => $spk])
            ->call('batalkan');

        $spk->refresh();
        $rambuPasang->refresh();

        $this->assertSame(StatusSpk::Dibatalkan, $spk->status);
        $this->assertSame(StatusRambuPasang::Batal, $rambuPasang->status);
        $this->assertSame(1, $spk->auditLogs()->where('aksi', 'spk_dibatalkan')->count());
        $this->assertSame(1, Notifikasi::where('user_id', $petugas->id)->where('judul', 'SPK Dibatalkan')->count());
        $this->assertSame(
            route('user.spk.show', $spk),
            Notifikasi::where('user_id', $petugas->id)->where('judul', 'SPK Dibatalkan')->first()->url
        );
    }
}
