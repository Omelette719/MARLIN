<?php

namespace Tests\Feature\Admin;

use App\Enums\JenisPekerjaan;
use App\Enums\StatusRambuPasang;
use App\Enums\Urgensi;
use App\Livewire\Admin\Spk\Create as SpkCreateComponent;
use App\Livewire\Admin\Spk\Index as SpkIndexComponent;
use App\Models\DikerjakanOleh;
use App\Models\JenisRambu;
use App\Models\Notifikasi;
use App\Models\Rambu;
use App\Models\RambuPasang;
use App\Models\Spk;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use Tests\TestCase;

class SpkTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_image_foto_survei_is_rejected_immediately_on_upload(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(SpkCreateComponent::class)
            ->set('rambuItems.0.foto_survei', UploadedFile::fake()->create('dokumen.pdf', 100, 'application/pdf'))
            ->assertSet('rambuItems.0.foto_survei', null)
            ->assertHasErrors(['rambuItems.0.foto_survei']);
    }

    public function test_admin_can_upload_a_pdf_as_file_referensi(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(SpkCreateComponent::class)
            ->set('file_referensi', UploadedFile::fake()->create('surat-permohonan.pdf', 100, 'application/pdf'))
            ->assertSet('file_referensi', fn ($file) => $file !== null)
            ->assertHasNoErrors(['file_referensi']);
    }

    public function test_admin_can_upload_an_image_as_file_referensi(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(SpkCreateComponent::class)
            ->set('file_referensi', UploadedFile::fake()->image('surat-permohonan.jpg'))
            ->assertSet('file_referensi', fn ($file) => $file !== null)
            ->assertHasNoErrors(['file_referensi']);
    }

    public function test_non_image_non_pdf_file_referensi_is_rejected_immediately_on_upload(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(SpkCreateComponent::class)
            ->set('file_referensi', UploadedFile::fake()->create('dokumen.docx', 100, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'))
            ->assertSet('file_referensi', null)
            ->assertHasErrors(['file_referensi']);
    }

    public function test_foto_survei_still_rejects_pdf_even_though_file_referensi_allows_it(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(SpkCreateComponent::class)
            ->set('file_referensi', UploadedFile::fake()->create('surat-permohonan.pdf', 100, 'application/pdf'))
            ->assertHasNoErrors(['file_referensi'])
            ->set('rambuItems.0.foto_survei', UploadedFile::fake()->create('dokumen.pdf', 100, 'application/pdf'))
            ->assertSet('rambuItems.0.foto_survei', null)
            ->assertHasErrors(['rambuItems.0.foto_survei']);
    }

    public function test_petugas_cannot_access_admin_spk_pages(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(route('admin.spk.index'))->assertRedirect(route('dashboard'));
        $this->get(route('admin.spk.create'))->assertRedirect(route('dashboard'));
    }

    public function test_admin_can_view_daftar_surat(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $this->get(route('admin.spk.index'))->assertOk();
    }

    public function test_daftar_surat_card_shows_photos_from_every_rambu_for_the_slideshow(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $jenisRambu = JenisRambu::create(['nama_jenis' => 'Rambu Peringatan']);

        $spk = Spk::create([
            'nomor_surat' => 'SR-2026/BJM/8020',
            'dibuat_oleh' => $admin->id,
            'jenis_spk' => 'pasang_baru',
            'wilayah' => 'Banjarmasin Tengah',
            'deadline' => now()->addDays(5),
            'urgensi' => 'sedang',
            'status' => 'aktif',
            'asal_permintaan' => 'internal',
        ]);

        foreach (['satu', 'dua'] as $label) {
            $rambu = Rambu::create([
                'jenis_rambu_id' => $jenisRambu->id,
                'wilayah' => 'Banjarmasin Tengah',
                'lokasi' => "Lokasi {$label}",
                'koordinat' => '-3.30,114.59',
            ]);

            RambuPasang::create([
                'rambu_spk_id' => $spk->id,
                'rambu_id' => $rambu->id,
                'jenis_pekerjaan' => 'pasang_baru',
                'jumlah' => 1,
                'status' => 'belum',
                'foto_survei' => "rambu-pasang/survei/contoh-{$label}.jpg",
            ]);
        }

        $response = $this->get(route('admin.spk.index'));

        $response->assertOk();
        $response->assertSee('rambu-pasang/survei/contoh-satu.jpg', false);
        $response->assertSee('rambu-pasang/survei/contoh-dua.jpg', false);
    }

    public function test_daftar_surat_card_shows_whether_a_team_has_joined(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $spkDenganTim = Spk::create([
            'nomor_surat' => 'SR-2026/BJM/8021',
            'dibuat_oleh' => $admin->id,
            'jenis_spk' => 'pasang_baru',
            'wilayah' => 'Banjarmasin Tengah',
            'deadline' => now()->addDays(5),
            'urgensi' => 'sedang',
            'status' => 'aktif',
            'asal_permintaan' => 'internal',
        ]);
        DikerjakanOleh::create([
            'by_spk_id' => $spkDenganTim->id,
            'by_user_id' => User::factory()->create()->id,
            'is_perwakilan' => true,
        ]);

        Spk::create([
            'nomor_surat' => 'SR-2026/BJM/8022',
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
        $response->assertSee('Tim Terdaftar');
        $response->assertSee('Belum Ada Tim');
    }

    public function test_admin_can_filter_daftar_surat_by_jenis(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        Spk::create([
            'nomor_surat' => 'SR-2026/BJM/8001',
            'dibuat_oleh' => $admin->id,
            'jenis_spk' => 'pasang_baru',
            'wilayah' => 'Banjarmasin Tengah',
            'deadline' => now()->addDays(5),
            'urgensi' => 'sedang',
            'status' => 'aktif',
            'asal_permintaan' => 'internal',
        ]);

        Spk::create([
            'nomor_surat' => 'SR-2026/BJM/8002',
            'dibuat_oleh' => $admin->id,
            'jenis_spk' => 'perbaikan',
            'wilayah' => 'Banjarmasin Selatan',
            'deadline' => now()->addDays(5),
            'urgensi' => 'sedang',
            'status' => 'aktif',
            'asal_permintaan' => 'internal',
        ]);

        Livewire::test(SpkIndexComponent::class)
            ->set('jenis', JenisPekerjaan::Perbaikan->value)
            ->assertSee('SR-2026/BJM/8002')
            ->assertDontSee('SR-2026/BJM/8001');
    }

    public function test_admin_can_create_spk_with_pasang_baru_rambu(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $jenisRambu = JenisRambu::create([
            'nama_jenis' => 'Rambu Peringatan',
            'spesifikasi_standar' => 'Bentuk belah ketupat.',
        ]);

        Livewire::test(SpkCreateComponent::class)
            ->set('jenis_spk', JenisPekerjaan::PasangBaru->value)
            ->set('jalan', 'Lambung Mangkurat')
            ->set('rt', '5')
            ->set('kelurahan', 'Kertak Baru')
            ->set('deadline', now()->addDays(5)->toDateString())
            ->set('asal_permintaan', 'internal')
            ->set('rambuItems.0.jenis_rambu_id', (string) $jenisRambu->id)
            ->set('rambuItems.0.lokasi', 'Perempatan dekat masjid')
            ->set('rambuItems.0.koordinat', '-3.3194,114.5908')
            ->set('rambuItems.0.jumlah', 2)
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('admin.spk.index'));

        $spk = Spk::first();

        $this->assertNotNull($spk);
        $this->assertSame('Jl. Lambung Mangkurat RT. 5 Kel. Kertak Baru', $spk->wilayah);
        $this->assertSame(JenisPekerjaan::PasangBaru, $spk->jenis_spk);
        $this->assertSame(Urgensi::Sedang, $spk->urgensi);
        $this->assertSame($admin->id, $spk->dibuat_oleh);

        $rambuPasang = $spk->rambuPasang()->first();
        $this->assertSame(JenisPekerjaan::PasangBaru, $rambuPasang->jenis_pekerjaan);
        $this->assertSame(StatusRambuPasang::Belum, $rambuPasang->status);
        $this->assertSame(2, $rambuPasang->jumlah);

        $this->assertSame(1, $spk->auditLogs()->count());
    }

    public function test_admin_can_create_spk_with_tanggal_survei_and_rt_perwakilan(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $jenisRambu = JenisRambu::create(['nama_jenis' => 'Cermin Tikungan']);

        Livewire::test(SpkCreateComponent::class)
            ->set('jenis_spk', JenisPekerjaan::PasangBaru->value)
            ->set('jalan', 'Gatot X')
            ->set('rt', '27')
            ->set('kelurahan', 'Pengambangan')
            ->set('perihal', 'pemasangan cermin tikungan')
            ->set('deadline', now()->addDays(5)->toDateString())
            ->set('asal_permintaan', 'internal')
            ->set('tanggal_survei', '2026-06-15')
            ->set('petugas_survei', 'Budi, Andi')
            ->set('rt_nama', 'RT. 27 Matoha')
            ->set('rt_telepon', '08981112210')
            ->set('rambuItems.0.jenis_rambu_id', (string) $jenisRambu->id)
            ->set('rambuItems.0.lokasi', 'Perempatan 1')
            ->set('rambuItems.0.koordinat', '-3.3194,114.5908')
            ->set('rambuItems.0.jumlah', 1)
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('admin.spk.index'));

        $spk = Spk::first();

        $this->assertNotNull($spk);
        $this->assertSame('Jl. Gatot X RT. 27 Kel. Pengambangan', $spk->wilayah);
        $this->assertSame('pemasangan cermin tikungan', $spk->perihal);
        $this->assertSame('2026-06-15', $spk->tanggal_survei->toDateString());
        $this->assertSame('Budi, Andi', $spk->petugas_survei);
        $this->assertSame(1, $spk->rtPerwakilan()->count());

        $rt = $spk->rtPerwakilan()->first();
        $this->assertSame('RT. 27 Matoha', $rt->nama_lengkap);
        $this->assertSame('08981112210', $rt->no_telepon);
    }

    public function test_petugas_survei_required_when_tanggal_survei_filled(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $jenisRambu = JenisRambu::create(['nama_jenis' => 'Cermin Tikungan']);

        Livewire::test(SpkCreateComponent::class)
            ->set('jenis_spk', JenisPekerjaan::PasangBaru->value)
            ->set('jalan', 'Gatot X')
            ->set('rt', '27')
            ->set('kelurahan', 'Pengambangan')
            ->set('deadline', now()->addDays(5)->toDateString())
            ->set('asal_permintaan', 'internal')
            ->set('tanggal_survei', '2026-06-15')
            ->set('petugas_survei', '')
            ->set('rambuItems.0.jenis_rambu_id', (string) $jenisRambu->id)
            ->set('rambuItems.0.lokasi', 'Perempatan 1')
            ->set('rambuItems.0.koordinat', '-3.3194,114.5908')
            ->set('rambuItems.0.jumlah', 1)
            ->call('save')
            ->assertHasErrors(['petugas_survei' => 'required_with']);

        $this->assertSame(0, Spk::count());
    }

    public function test_admin_can_create_spk_without_tanggal_survei_or_rt_perwakilan(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $jenisRambu = JenisRambu::create(['nama_jenis' => 'Rambu Peringatan']);

        Livewire::test(SpkCreateComponent::class)
            ->set('jenis_spk', JenisPekerjaan::PasangBaru->value)
            ->set('jalan', 'Lambung Mangkurat')
            ->set('rt', '5')
            ->set('kelurahan', 'Kertak Baru')
            ->set('deadline', now()->addDays(5)->toDateString())
            ->set('asal_permintaan', 'internal')
            ->set('rambuItems.0.jenis_rambu_id', (string) $jenisRambu->id)
            ->set('rambuItems.0.lokasi', 'Perempatan dekat masjid')
            ->set('rambuItems.0.koordinat', '-3.3194,114.5908')
            ->set('rambuItems.0.jumlah', 1)
            ->call('save')
            ->assertHasNoErrors();

        $spk = Spk::first();

        $this->assertNull($spk->tanggal_survei);
        $this->assertSame(0, $spk->rtPerwakilan()->count());
    }

    public function test_creating_spk_broadcasts_notifikasi_to_active_petugas(): void
    {
        $admin = User::factory()->admin()->create();
        $activePetugas = User::factory()->create(['aktif' => true]);
        $inactivePetugas = User::factory()->create(['aktif' => false]);
        $this->actingAs($admin);

        $jenisRambu = JenisRambu::create(['nama_jenis' => 'Rambu Peringatan']);

        Livewire::test(SpkCreateComponent::class)
            ->set('jalan', 'Lambung Mangkurat')
            ->set('rt', '5')
            ->set('kelurahan', 'Kertak Baru')
            ->set('deadline', now()->addDays(5)->toDateString())
            ->set('asal_permintaan', 'internal')
            ->set('rambuItems.0.jenis_rambu_id', (string) $jenisRambu->id)
            ->set('rambuItems.0.lokasi', 'Perempatan dekat masjid')
            ->set('rambuItems.0.koordinat', '-3.3194,114.5908')
            ->set('rambuItems.0.jumlah', 1)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(1, Notifikasi::where('user_id', $activePetugas->id)->count());
        $this->assertSame(0, Notifikasi::where('user_id', $inactivePetugas->id)->count());
        $this->assertSame(0, Notifikasi::where('user_id', $admin->id)->count());

        $spk = Spk::first();
        $this->assertSame(route('user.spk.show', $spk), Notifikasi::where('user_id', $activePetugas->id)->first()->url);
    }

    public function test_admin_can_create_spk_with_perbaikan_referencing_existing_rambu(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $jenisRambu = JenisRambu::create(['nama_jenis' => 'Rambu Larangan']);
        $rambu = Rambu::create([
            'jenis_rambu_id' => $jenisRambu->id,
            'wilayah' => 'Banjarmasin Utara',
            'lokasi' => 'Depan pasar',
            'koordinat' => '-3.30,114.59',
            'kondisi_terkini' => 'rusak',
            'sudah_terpasang' => true,
        ]);

        Livewire::test(SpkCreateComponent::class)
            ->set('jenis_spk', JenisPekerjaan::Perbaikan->value)
            ->set('jalan', 'Veteran')
            ->set('rt', '10')
            ->set('kelurahan', 'Antasan Besar')
            ->set('deadline', now()->addDays(1)->toDateString())
            ->set('asal_permintaan', 'laporan_masyarakat')
            ->set('rambuItems.0.rambu_id', (string) $rambu->id)
            ->set('rambuItems.0.jumlah', 1)
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('admin.spk.index'));

        $this->assertSame(1, Rambu::count());

        $spk = Spk::first();
        $this->assertSame(JenisPekerjaan::Perbaikan, $spk->jenis_spk);
        $this->assertSame(Urgensi::Tinggi, $spk->urgensi);

        $rambuPasang = $spk->rambuPasang()->first();
        $this->assertSame(JenisPekerjaan::Perbaikan, $rambuPasang->jenis_pekerjaan);
        $this->assertSame($rambu->id, $rambuPasang->rambu_id);
    }

    public function test_admin_can_create_spk_with_perbaikan_for_rambu_not_yet_in_system(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $jenisRambu = JenisRambu::create(['nama_jenis' => 'Rambu Larangan']);

        Livewire::test(SpkCreateComponent::class)
            ->set('jenis_spk', JenisPekerjaan::Perbaikan->value)
            ->set('jalan', 'Veteran')
            ->set('rt', '10')
            ->set('kelurahan', 'Antasan Besar')
            ->set('deadline', now()->addDays(1)->toDateString())
            ->set('asal_permintaan', 'laporan_masyarakat')
            ->set('rambuItems.0.rambu_terdaftar', false)
            ->set('rambuItems.0.jenis_rambu_id', (string) $jenisRambu->id)
            ->set('rambuItems.0.lokasi', 'Depan kantor camat')
            ->set('rambuItems.0.koordinat', '-3.35,114.60')
            ->set('rambuItems.0.jumlah', 1)
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('admin.spk.index'));

        $rambu = Rambu::first();
        $this->assertNotNull($rambu);
        $this->assertSame('Jl. Veteran RT. 10', $rambu->wilayah);
        $this->assertSame('rusak', $rambu->kondisi_terkini->value);
        $this->assertTrue($rambu->sudah_terpasang);

        $rambuPasang = Spk::first()->rambuPasang()->first();
        $this->assertSame(JenisPekerjaan::Perbaikan, $rambuPasang->jenis_pekerjaan);
        $this->assertSame($rambu->id, $rambuPasang->rambu_id);
    }

    public function test_rambu_items_are_required(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(SpkCreateComponent::class)
            ->set('jalan', 'Veteran')
            ->set('rt', '10')
            ->set('kelurahan', 'Antasan Besar')
            ->set('deadline', now()->addDays(10)->toDateString())
            ->set('asal_permintaan', 'internal')
            ->set('rambuItems', [])
            ->call('save')
            ->assertHasErrors(['rambuItems']);
    }

    public function test_koordinat_with_unparsable_format_is_rejected(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $jenisRambu = JenisRambu::create(['nama_jenis' => 'Rambu Peringatan']);

        Livewire::test(SpkCreateComponent::class)
            ->set('jenis_spk', JenisPekerjaan::PasangBaru->value)
            ->set('jalan', 'Lambung Mangkurat')
            ->set('rt', '5')
            ->set('kelurahan', 'Kertak Baru')
            ->set('deadline', now()->addDays(5)->toDateString())
            ->set('asal_permintaan', 'internal')
            ->set('rambuItems.0.jenis_rambu_id', (string) $jenisRambu->id)
            ->set('rambuItems.0.lokasi', 'Perempatan dekat masjid')
            ->set('rambuItems.0.koordinat', 'dekat masjid raya')
            ->set('rambuItems.0.jumlah', 1)
            ->call('save')
            ->assertHasErrors(['rambuItems.0.koordinat']);

        $this->assertSame(0, Spk::count());
    }

    public function test_koordinat_outside_valid_lat_lng_range_is_rejected(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $jenisRambu = JenisRambu::create(['nama_jenis' => 'Rambu Peringatan']);

        Livewire::test(SpkCreateComponent::class)
            ->set('jenis_spk', JenisPekerjaan::PasangBaru->value)
            ->set('jalan', 'Lambung Mangkurat')
            ->set('rt', '5')
            ->set('kelurahan', 'Kertak Baru')
            ->set('deadline', now()->addDays(5)->toDateString())
            ->set('asal_permintaan', 'internal')
            ->set('rambuItems.0.jenis_rambu_id', (string) $jenisRambu->id)
            ->set('rambuItems.0.lokasi', 'Perempatan dekat masjid')
            ->set('rambuItems.0.koordinat', '200,300')
            ->set('rambuItems.0.jumlah', 1)
            ->call('save')
            ->assertHasErrors(['rambuItems.0.koordinat']);

        $this->assertSame(0, Spk::count());
    }

    public function test_cannot_select_the_same_existing_rambu_twice_in_one_spk(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $jenisRambu = JenisRambu::create(['nama_jenis' => 'Rambu Larangan']);
        $rambu = Rambu::create([
            'jenis_rambu_id' => $jenisRambu->id,
            'wilayah' => 'Banjarmasin Utara',
            'lokasi' => 'Depan pasar',
            'koordinat' => '-3.30,114.59',
            'kondisi_terkini' => 'rusak',
            'sudah_terpasang' => true,
        ]);

        Livewire::test(SpkCreateComponent::class)
            ->set('jenis_spk', JenisPekerjaan::Perbaikan->value)
            ->set('jalan', 'Veteran')
            ->set('rt', '10')
            ->set('kelurahan', 'Antasan Besar')
            ->set('deadline', now()->addDays(1)->toDateString())
            ->set('asal_permintaan', 'laporan_masyarakat')
            ->set('rambuItems.0.rambu_id', (string) $rambu->id)
            ->set('rambuItems.0.jumlah', 1)
            ->call('addRambuItem')
            ->set('rambuItems.1.rambu_id', (string) $rambu->id)
            ->set('rambuItems.1.jumlah', 1)
            ->call('save')
            ->assertHasErrors(['rambuItems']);

        $this->assertSame(0, Spk::count());
    }

    public function test_live_koordinat_input_warns_about_nearby_existing_rambu(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $jenisRambuExisting = JenisRambu::create(['nama_jenis' => 'Rambu Larangan']);
        Rambu::create([
            'jenis_rambu_id' => $jenisRambuExisting->id,
            'wilayah' => 'Banjarmasin Utara',
            'lokasi' => 'Depan pasar lama',
            'koordinat' => '-3.3194,114.5908',
            'sudah_terpasang' => true,
        ]);

        $jenisRambuBaru = JenisRambu::create(['nama_jenis' => 'Rambu Peringatan']);

        $component = Livewire::test(SpkCreateComponent::class)
            ->set('jenis_spk', JenisPekerjaan::PasangBaru->value)
            ->set('rambuItems.0.jenis_rambu_id', (string) $jenisRambuBaru->id)
            ->set('rambuItems.0.koordinat', '-3.3194,114.5908');

        $warnings = $component->get('koordinatWarnings');

        $this->assertNotEmpty($warnings[0] ?? null);
        $this->assertStringContainsString('Depan pasar lama', $warnings[0][0]['label']);
    }

    public function test_rt_telepon_with_letters_is_rejected(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $jenisRambu = JenisRambu::create(['nama_jenis' => 'Rambu Peringatan']);

        Livewire::test(SpkCreateComponent::class)
            ->set('jenis_spk', JenisPekerjaan::PasangBaru->value)
            ->set('jalan', 'Lambung Mangkurat')
            ->set('rt', '5')
            ->set('kelurahan', 'Kertak Baru')
            ->set('deadline', now()->addDays(5)->toDateString())
            ->set('asal_permintaan', 'internal')
            ->set('rt_nama', 'Abdul')
            ->set('rt_telepon', 'bukan nomor telepon')
            ->set('rambuItems.0.jenis_rambu_id', (string) $jenisRambu->id)
            ->set('rambuItems.0.lokasi', 'Perempatan dekat masjid')
            ->set('rambuItems.0.koordinat', '-3.3194,114.5908')
            ->set('rambuItems.0.jumlah', 1)
            ->call('save')
            ->assertHasErrors(['rt_telepon' => 'regex']);

        $this->assertSame(0, Spk::count());
    }

    public function test_invalid_asal_permintaan_value_is_rejected(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $jenisRambu = JenisRambu::create(['nama_jenis' => 'Rambu Peringatan']);

        Livewire::test(SpkCreateComponent::class)
            ->set('jenis_spk', JenisPekerjaan::PasangBaru->value)
            ->set('jalan', 'Lambung Mangkurat')
            ->set('rt', '5')
            ->set('kelurahan', 'Kertak Baru')
            ->set('deadline', now()->addDays(5)->toDateString())
            ->set('asal_permintaan', 'bukan_asal_yang_valid')
            ->set('rambuItems.0.jenis_rambu_id', (string) $jenisRambu->id)
            ->set('rambuItems.0.lokasi', 'Perempatan dekat masjid')
            ->set('rambuItems.0.koordinat', '-3.3194,114.5908')
            ->set('rambuItems.0.jumlah', 1)
            ->call('save')
            ->assertHasErrors(['asal_permintaan']);

        $this->assertSame(0, Spk::count());
    }

    public function test_live_koordinat_input_has_no_warning_when_no_rambu_nearby(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $jenisRambu = JenisRambu::create(['nama_jenis' => 'Rambu Peringatan']);

        $component = Livewire::test(SpkCreateComponent::class)
            ->set('jenis_spk', JenisPekerjaan::PasangBaru->value)
            ->set('rambuItems.0.jenis_rambu_id', (string) $jenisRambu->id)
            ->set('rambuItems.0.koordinat', '-3.3194,114.5908');

        $this->assertEmpty($component->get('koordinatWarnings')[0] ?? null);
    }
}
