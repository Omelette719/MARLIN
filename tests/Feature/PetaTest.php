<?php

namespace Tests\Feature;

use App\Models\JenisRambu;
use App\Models\Rambu;
use App\Models\RambuPasang;
use App\Models\Spk;
use App\Models\User;
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
}
