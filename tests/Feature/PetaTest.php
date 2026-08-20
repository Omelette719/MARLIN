<?php

namespace Tests\Feature;

use App\Models\JenisRambu;
use App\Models\Rambu;
use App\Models\RambuPasang;
use App\Models\Spk;
use App\Models\User;
use App\Support\PetaData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class PetaTest extends TestCase
{
    use RefreshDatabase;

    private function makeSpk(User $admin, string $urgensi = 'sedang', bool $prioritas = false): Spk
    {
        return Spk::create([
            'nomor_surat' => 'SR-2026/BJM/'.random_int(1000, 9999),
            'dibuat_oleh' => $admin->id,
            'wilayah' => 'Banjarmasin Tengah',
            'deadline' => now()->addDays(5),
            'urgensi' => $urgensi,
            'prioritas' => $prioritas,
            'status' => 'aktif',
            'asal_permintaan' => 'internal',
        ]);
    }

    private function makeRambu(bool $sudahTerpasang = false, string $kondisi = 'baik'): Rambu
    {
        $jenisRambu = JenisRambu::firstOrCreate(['nama_jenis' => 'Rambu Peringatan']);

        return Rambu::create([
            'jenis_rambu_id' => $jenisRambu->id,
            'wilayah' => 'Banjarmasin Tengah',
            'lokasi' => 'Perempatan dekat masjid',
            'koordinat' => '-3.3194,114.5908',
            'kondisi_terkini' => $kondisi,
            'sudah_terpasang' => $sudahTerpasang,
        ]);
    }

    public function test_both_roles_can_load_peta_page(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $this->get(route('peta'))->assertOk();

        $this->actingAs(User::factory()->create());
        $this->get(route('peta'))->assertOk();
    }

    // Unlike the dashboard widgets' Tingkat dropdown (which skips "selesai"
    // since that's hidden by default there), this page has no such bias, so
    // "Selesai / Kondisi Baik" has to be a real, selectable option here.
    public function test_peta_page_shows_full_filter_row_including_selesai_tingkat(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $response = $this->get(route('peta'));

        $response->assertOk();
        $response->assertSee('id="peta-filter-jenis"', false);
        $response->assertSee('id="peta-filter-tingkat"', false);
        $response->assertSee('id="peta-filter-kecamatan"', false);
        $response->assertSee('id="peta-filter-kelurahan"', false);
        $response->assertSee('id="peta-unduh-pdf"', false);
        $response->assertSee('Selesai / Kondisi Baik');
    }

    public function test_pin_with_active_task_includes_raw_status_and_spk_fields(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $rambu = $this->makeRambu();
        $spk = $this->makeSpk($admin, urgensi: 'tinggi', prioritas: true);

        RambuPasang::create([
            'rambu_spk_id' => $spk->id,
            'rambu_id' => $rambu->id,
            'jenis_pekerjaan' => 'pasang_baru',
            'jumlah' => 1,
            'status' => 'belum',
        ]);

        $response = $this->getJson(route('peta.data'));
        $response->assertOk();

        $pin = collect($response->json())->firstWhere('id', $rambu->id);

        $this->assertNotNull($pin);
        $this->assertSame('belum', $pin['status']);
        $this->assertSame('pasang_baru', $pin['jenis_pekerjaan']);
        $this->assertSame($spk->nomor_surat, $pin['spk']['nomor_surat']);
        $this->assertTrue($pin['spk']['prioritas']);
        $this->assertSame('tinggi', $pin['spk']['urgensi']);
    }

    public function test_pin_hidden_when_not_installed_and_only_task_is_batal(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $rambu = $this->makeRambu(sudahTerpasang: false);
        $spk = $this->makeSpk($admin);

        RambuPasang::create([
            'rambu_spk_id' => $spk->id,
            'rambu_id' => $rambu->id,
            'jenis_pekerjaan' => 'pasang_baru',
            'jumlah' => 1,
            'status' => 'batal',
        ]);

        $response = $this->getJson(route('peta.data'));

        $pin = collect($response->json())->firstWhere('id', $rambu->id);
        $this->assertNull($pin);
    }

    public function test_pin_still_shown_when_installed_even_if_latest_task_is_batal(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $rambu = $this->makeRambu(sudahTerpasang: true, kondisi: 'baik');
        $spk = $this->makeSpk($admin);

        RambuPasang::create([
            'rambu_spk_id' => $spk->id,
            'rambu_id' => $rambu->id,
            'jenis_pekerjaan' => 'perbaikan',
            'jumlah' => 1,
            'status' => 'batal',
        ]);

        $response = $this->getJson(route('peta.data'));

        $pin = collect($response->json())->firstWhere('id', $rambu->id);
        $this->assertNotNull($pin);
        $this->assertNull($pin['status']);
        $this->assertNull($pin['spk']);
    }

    public function test_pin_includes_icon_and_shape_from_jenis_rambu(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $jenisRambu = JenisRambu::create([
            'nama_jenis' => 'Rambu Larangan',
            'bentuk_ikon' => 'bulat',
            'gambar_referensi' => 'jenis-rambu/rambu-larangan.svg',
        ]);

        $rambu = Rambu::create([
            'jenis_rambu_id' => $jenisRambu->id,
            'wilayah' => 'Banjarmasin Tengah',
            'lokasi' => 'Depan pasar',
            'koordinat' => '-3.30,114.59',
            'sudah_terpasang' => true,
        ]);

        $response = $this->getJson(route('peta.data'));

        $pin = collect($response->json())->firstWhere('id', $rambu->id);

        $this->assertNotNull($pin);
        $this->assertSame('bulat', $pin['bentuk_ikon']);
        $this->assertStringContainsString('jenis-rambu/rambu-larangan.svg', $pin['ikon']);
    }

    public function test_pin_defaults_to_bulat_shape_and_null_icon_when_not_set(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $rambu = $this->makeRambu(sudahTerpasang: true);

        $response = $this->getJson(route('peta.data'));

        $pin = collect($response->json())->firstWhere('id', $rambu->id);

        $this->assertNotNull($pin);
        $this->assertSame('bulat', $pin['bentuk_ikon']);
        $this->assertNull($pin['ikon']);
    }

    public function test_pin_foto_uses_real_photo_not_icon(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $jenisRambu = JenisRambu::create([
            'nama_jenis' => 'Rambu Larangan',
            'gambar_referensi' => 'jenis-rambu/rambu-larangan.svg',
        ]);

        $rambu = Rambu::create([
            'jenis_rambu_id' => $jenisRambu->id,
            'wilayah' => 'Banjarmasin Tengah',
            'lokasi' => 'Depan pasar',
            'koordinat' => '-3.30,114.59',
            'sudah_terpasang' => true,
        ]);

        RambuPasang::create([
            'rambu_spk_id' => $this->makeSpk($admin)->id,
            'rambu_id' => $rambu->id,
            'jenis_pekerjaan' => 'pasang_baru',
            'jumlah' => 1,
            'status' => 'selesai',
            'foto_survei' => 'rambu-pasang/survei/contoh.jpg',
        ]);

        $response = $this->getJson(route('peta.data'));
        $pin = collect($response->json())->firstWhere('id', $rambu->id);

        $this->assertNotNull($pin);
        $this->assertStringContainsString('rambu-pasang/survei/contoh.jpg', $pin['foto']);
        $this->assertStringContainsString('jenis-rambu/rambu-larangan.svg', $pin['ikon']);
        $this->assertNotSame($pin['foto'], $pin['ikon']);
    }

    public function test_pin_foto_null_when_no_real_photo_available(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $rambu = $this->makeRambu(sudahTerpasang: true);

        $response = $this->getJson(route('peta.data'));
        $pin = collect($response->json())->firstWhere('id', $rambu->id);

        $this->assertNotNull($pin);
        $this->assertNull($pin['foto']);
    }

    public function test_pin_hidden_when_koordinat_invalid(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $jenisRambu = JenisRambu::create(['nama_jenis' => 'Rambu Larangan']);
        $rambu = Rambu::create([
            'jenis_rambu_id' => $jenisRambu->id,
            'wilayah' => 'Banjarmasin Tengah',
            'lokasi' => 'Lokasi tanpa koordinat valid',
            'koordinat' => 'tidak-valid',
            'sudah_terpasang' => true,
        ]);

        RambuPasang::create([
            'rambu_spk_id' => $this->makeSpk($admin)->id,
            'rambu_id' => $rambu->id,
            'jenis_pekerjaan' => 'pasang_baru',
            'jumlah' => 1,
            'status' => 'selesai',
        ]);

        $response = $this->getJson(route('peta.data'));

        $pin = collect($response->json())->firstWhere('id', $rambu->id);
        $this->assertNull($pin);
    }

    public function test_peta_data_can_be_filtered_by_jenis_rambu(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $jenisA = JenisRambu::create(['nama_jenis' => 'Rambu Peringatan A']);
        $jenisB = JenisRambu::create(['nama_jenis' => 'Rambu Peringatan B']);

        $rambuA = Rambu::create([
            'jenis_rambu_id' => $jenisA->id,
            'wilayah' => 'Banjarmasin Tengah',
            'lokasi' => 'A',
            'koordinat' => '-3.30,114.59',
            'sudah_terpasang' => true,
        ]);
        $rambuB = Rambu::create([
            'jenis_rambu_id' => $jenisB->id,
            'wilayah' => 'Banjarmasin Tengah',
            'lokasi' => 'B',
            'koordinat' => '-3.31,114.60',
            'sudah_terpasang' => true,
        ]);

        $response = $this->getJson(route('peta.data', ['jenis_rambu_id' => $jenisA->id]));
        $ids = collect($response->json())->pluck('id');

        $this->assertTrue($ids->contains($rambuA->id));
        $this->assertFalse($ids->contains($rambuB->id));
    }

    public function test_peta_data_can_be_filtered_by_tingkat_tinggi(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $rambuUrgent = $this->makeRambu();
        $spkPrioritas = $this->makeSpk($admin, urgensi: 'tinggi', prioritas: true);
        RambuPasang::create([
            'rambu_spk_id' => $spkPrioritas->id,
            'rambu_id' => $rambuUrgent->id,
            'jenis_pekerjaan' => 'pasang_baru',
            'jumlah' => 1,
            'status' => 'belum',
        ]);

        $rambuTenang = $this->makeRambu(sudahTerpasang: true, kondisi: 'baik');

        $response = $this->getJson(route('peta.data', ['tingkat' => 'tinggi']));
        $ids = collect($response->json())->pluck('id');

        $this->assertTrue($ids->contains($rambuUrgent->id));
        $this->assertFalse($ids->contains($rambuTenang->id));
    }

    public function test_peta_data_menunggu_validasi_takes_priority_over_urgent_tingkat(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $rambu = $this->makeRambu();
        $spkPrioritas = $this->makeSpk($admin, urgensi: 'tinggi', prioritas: true);
        RambuPasang::create([
            'rambu_spk_id' => $spkPrioritas->id,
            'rambu_id' => $rambu->id,
            'jenis_pekerjaan' => 'pasang_baru',
            'jumlah' => 1,
            'status' => 'menunggu_validasi',
        ]);

        $response = $this->getJson(route('peta.data'));
        $pin = collect($response->json())->firstWhere('id', $rambu->id);

        $this->assertSame('menunggu_validasi', $pin['tingkat']);
    }

    public function test_peta_data_can_be_filtered_by_kelurahan(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $jenisRambu = JenisRambu::firstOrCreate(['nama_jenis' => 'Rambu Peringatan']);
        $rambuPengambangan = Rambu::create([
            'jenis_rambu_id' => $jenisRambu->id,
            'wilayah' => 'Banjarmasin Timur',
            'kelurahan' => 'Pengambangan',
            'lokasi' => 'A',
            'koordinat' => '-3.30,114.59',
            'sudah_terpasang' => true,
        ]);
        $rambuTelawang = Rambu::create([
            'jenis_rambu_id' => $jenisRambu->id,
            'wilayah' => 'Banjarmasin Barat',
            'kelurahan' => 'Telawang',
            'lokasi' => 'B',
            'koordinat' => '-3.31,114.60',
            'sudah_terpasang' => true,
        ]);

        $response = $this->getJson(route('peta.data', ['kelurahan' => 'Pengambangan']));
        $ids = collect($response->json())->pluck('id');

        $this->assertTrue($ids->contains($rambuPengambangan->id));
        $this->assertFalse($ids->contains($rambuTelawang->id));
    }

    public function test_peta_data_can_be_filtered_by_kecamatan(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        // Pengambangan and Kuripan are both in Kecamatan Banjarmasin Timur;
        // Telawang is in Banjarmasin Barat — the kecamatan filter has to
        // resolve to every kelurahan under it, not just an exact match.
        $jenisRambu = JenisRambu::firstOrCreate(['nama_jenis' => 'Rambu Peringatan']);
        $rambuPengambangan = Rambu::create([
            'jenis_rambu_id' => $jenisRambu->id,
            'wilayah' => 'Banjarmasin Timur',
            'kelurahan' => 'Pengambangan',
            'lokasi' => 'A',
            'koordinat' => '-3.30,114.59',
            'sudah_terpasang' => true,
        ]);
        $rambuKuripan = Rambu::create([
            'jenis_rambu_id' => $jenisRambu->id,
            'wilayah' => 'Banjarmasin Timur',
            'kelurahan' => 'Kuripan',
            'lokasi' => 'B',
            'koordinat' => '-3.31,114.60',
            'sudah_terpasang' => true,
        ]);
        $rambuTelawang = Rambu::create([
            'jenis_rambu_id' => $jenisRambu->id,
            'wilayah' => 'Banjarmasin Barat',
            'kelurahan' => 'Telawang',
            'lokasi' => 'C',
            'koordinat' => '-3.32,114.61',
            'sudah_terpasang' => true,
        ]);

        $response = $this->getJson(route('peta.data', ['kecamatan' => 'Banjarmasin Timur']));
        $ids = collect($response->json())->pluck('id');

        $this->assertTrue($ids->contains($rambuPengambangan->id));
        $this->assertTrue($ids->contains($rambuKuripan->id));
        $this->assertFalse($ids->contains($rambuTelawang->id));
    }

    public function test_admin_can_export_peta_pdf(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $response = $this->get(route('peta.export'));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_petugas_can_also_export_peta_pdf(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get(route('peta.export'));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_peta_export_accepts_post_with_captured_map_image(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $response = $this->post(route('peta.export'), [
            'gambar_peta' => UploadedFile::fake()->image('peta.png'),
        ]);

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_peta_export_rejects_non_image_upload(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $response = $this->post(route('peta.export'), [
            'gambar_peta' => UploadedFile::fake()->create('bukan-gambar.pdf', 100, 'application/pdf'),
        ]);

        $response->assertSessionHasErrors('gambar_peta');
    }

    public function test_peta_data_analytics_are_computed_correctly(): void
    {
        $jenis = JenisRambu::create(['nama_jenis' => 'Rambu Peringatan']);

        Rambu::create([
            'jenis_rambu_id' => $jenis->id,
            'wilayah' => 'Banjarmasin Tengah',
            'kelurahan' => 'Antasan Besar',
            'lokasi' => 'A',
            'koordinat' => '-3.30,114.59',
            'sudah_terpasang' => true,
            'kondisi_terkini' => 'baik',
        ]);

        Rambu::create([
            'jenis_rambu_id' => $jenis->id,
            'wilayah' => 'Banjarmasin Utara',
            'kelurahan' => 'Sungai Miai',
            'lokasi' => 'B',
            'koordinat' => '-3.31,114.60',
            'sudah_terpasang' => true,
            'kondisi_terkini' => 'rusak',
        ]);

        $data = PetaData::build([]);

        $this->assertSame(2, $data['total']);
        $this->assertSame(1, $data['perTingkat']['selesai']);
        // A rusak sign with no active repair SPK has no urgensi tier to key
        // off, so it falls to the same "rendah" bucket as any other pin
        // without an elevated urgency.
        $this->assertSame(1, $data['perTingkat']['rendah']);
        $this->assertSame(1, $data['perKecamatan']['Banjarmasin Tengah']);
        $this->assertSame(1, $data['perKecamatan']['Banjarmasin Utara']);
        $this->assertSame(2, $data['perJenis']['Rambu Peringatan']);
    }

    public function test_peta_data_per_kecamatan_groups_pins_with_no_kelurahan_as_tidak_diketahui(): void
    {
        $jenis = JenisRambu::create(['nama_jenis' => 'Rambu Peringatan']);

        Rambu::create([
            'jenis_rambu_id' => $jenis->id,
            'wilayah' => 'Jl. Tanpa Kelurahan',
            'lokasi' => 'A',
            'koordinat' => '-3.30,114.59',
            'sudah_terpasang' => true,
        ]);

        $data = PetaData::build([]);

        $this->assertSame(1, $data['perKecamatan']['Tidak diketahui']);
    }

    // Used by the PDF export's "Daftar SPK" table — only the SPK behind a
    // pin that actually made it through the current filter, and only while
    // that SPK is still Aktif (a Selesai/Dibatalkan SPK isn't open work, so
    // it has no place in a report about what's currently on the map).
    public function test_peta_data_spk_terkait_only_includes_active_spk_behind_filtered_pins(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $rambuAktif = $this->makeRambu();
        $spkAktif = $this->makeSpk($admin);
        RambuPasang::create([
            'rambu_spk_id' => $spkAktif->id,
            'rambu_id' => $rambuAktif->id,
            'jenis_pekerjaan' => 'pasang_baru',
            'jumlah' => 1,
            'status' => 'belum',
        ]);

        $rambuSelesai = $this->makeRambu();
        $spkSelesai = $this->makeSpk($admin);
        $spkSelesai->update(['status' => 'selesai']);
        RambuPasang::create([
            'rambu_spk_id' => $spkSelesai->id,
            'rambu_id' => $rambuSelesai->id,
            'jenis_pekerjaan' => 'pasang_baru',
            'jumlah' => 1,
            'status' => 'selesai',
        ]);

        $rambuTanpaSpk = $this->makeRambu(sudahTerpasang: true, kondisi: 'baik');

        $spkTerkait = PetaData::build([])['spkTerkait'];

        $this->assertTrue($spkTerkait->contains('id', $spkAktif->id));
        $this->assertFalse($spkTerkait->contains('id', $spkSelesai->id));
        $this->assertCount(1, $spkTerkait);
    }
}
