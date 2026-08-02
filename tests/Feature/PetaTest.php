<?php

namespace Tests\Feature;

use App\Models\JenisRambu;
use App\Models\Rambu;
use App\Models\RambuPasang;
use App\Models\Spk;
use App\Models\User;
use App\Support\PetaData;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    public function test_peta_data_can_be_filtered_by_tingkat_urgent(): void
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

        $response = $this->getJson(route('peta.data', ['tingkat' => 'urgent']));
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

    public function test_peta_data_can_be_filtered_by_task_date_range(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $rambuLama = $this->makeRambu();
        $spk = $this->makeSpk($admin);
        $tugasLama = RambuPasang::create([
            'rambu_spk_id' => $spk->id,
            'rambu_id' => $rambuLama->id,
            'jenis_pekerjaan' => 'pasang_baru',
            'jumlah' => 1,
            'status' => 'belum',
        ]);
        $tugasLama->created_at = now()->subYear();
        $tugasLama->save();

        $rambuBaru = $this->makeRambu();
        RambuPasang::create([
            'rambu_spk_id' => $this->makeSpk($admin)->id,
            'rambu_id' => $rambuBaru->id,
            'jenis_pekerjaan' => 'pasang_baru',
            'jumlah' => 1,
            'status' => 'belum',
        ]);

        $response = $this->getJson(route('peta.data', [
            'tanggal_dari' => now()->subDay()->toDateString(),
            'tanggal_sampai' => now()->toDateString(),
        ]));
        $ids = collect($response->json())->pluck('id');

        $this->assertTrue($ids->contains($rambuBaru->id));
        $this->assertFalse($ids->contains($rambuLama->id));
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

    public function test_peta_data_analytics_are_computed_correctly(): void
    {
        $jenis = JenisRambu::create(['nama_jenis' => 'Rambu Peringatan']);

        Rambu::create([
            'jenis_rambu_id' => $jenis->id,
            'wilayah' => 'Banjarmasin Tengah',
            'lokasi' => 'A',
            'koordinat' => '-3.30,114.59',
            'sudah_terpasang' => true,
            'kondisi_terkini' => 'baik',
        ]);

        Rambu::create([
            'jenis_rambu_id' => $jenis->id,
            'wilayah' => 'Banjarmasin Utara',
            'lokasi' => 'B',
            'koordinat' => '-3.31,114.60',
            'sudah_terpasang' => true,
            'kondisi_terkini' => 'rusak',
        ]);

        $data = PetaData::build([]);

        $this->assertSame(2, $data['total']);
        $this->assertSame(1, $data['perTingkat']['selesai']);
        $this->assertSame(1, $data['perTingkat']['rusak']);
        $this->assertSame(1, $data['perWilayah']['Banjarmasin Tengah']);
        $this->assertSame(1, $data['perWilayah']['Banjarmasin Utara']);
        $this->assertSame(2, $data['perJenis']['Rambu Peringatan']);
    }
}
