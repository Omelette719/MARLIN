<?php

namespace Tests\Feature\Admin;

use App\Enums\StatusRambuPasang;
use App\Enums\StatusSpk;
use App\Enums\Urgensi;
use App\Livewire\Admin\Spk\Edit as SpkEditComponent;
use App\Livewire\Admin\Spk\Riwayat;
use App\Livewire\Admin\Spk\Show as SpkShowComponent;
use App\Models\JenisRambu;
use App\Models\Rambu;
use App\Models\RambuPasang;
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

        Livewire::test(SpkShowComponent::class, ['spk' => $spk])
            ->call('batalkan');

        $spk->refresh();
        $rambuPasang->refresh();

        $this->assertSame(StatusSpk::Dibatalkan, $spk->status);
        $this->assertSame(StatusRambuPasang::Batal, $rambuPasang->status);
        $this->assertSame(1, $spk->auditLogs()->where('aksi', 'spk_dibatalkan')->count());
    }
}
