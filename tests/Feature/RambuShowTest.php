<?php

namespace Tests\Feature;

use App\Models\JenisRambu;
use App\Models\LaporanKondisi;
use App\Models\Rambu;
use App\Models\RambuPasang;
use App\Models\Spk;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RambuShowTest extends TestCase
{
    use RefreshDatabase;

    private function makeRambuWithRiwayat(User $admin): Rambu
    {
        $jenisRambu = JenisRambu::create(['nama_jenis' => 'Rambu Peringatan']);

        $rambu = Rambu::create([
            'jenis_rambu_id' => $jenisRambu->id,
            'wilayah' => 'Banjarmasin Tengah',
            'lokasi' => 'Depan pasar lama',
            'koordinat' => '-3.30,114.59',
        ]);

        $spk = Spk::create([
            'nomor_surat' => 'SR-2026/BJM/0099',
            'dibuat_oleh' => $admin->id,
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
            'foto_survei' => 'rambu-pasang/survei/contoh-detail.jpg',
        ]);

        return $rambu;
    }

    public function test_admin_can_view_rambu_detail(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $rambu = $this->makeRambuWithRiwayat($admin);

        $response = $this->get(route('rambu.show', $rambu));

        $response->assertOk();
        $response->assertSee('Depan pasar lama');
        $response->assertSee('rambu-pasang/survei/contoh-detail.jpg', false);
        $response->assertSee('SR-2026/BJM/0099');
    }

    public function test_petugas_can_also_view_rambu_detail(): void
    {
        $admin = User::factory()->admin()->create();
        $rambu = $this->makeRambuWithRiwayat($admin);

        $this->actingAs(User::factory()->create());

        $response = $this->get(route('rambu.show', $rambu));

        $response->assertOk();
        $response->assertSee('Depan pasar lama');
    }

    public function test_rambu_detail_shows_google_maps_and_peta_links(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $rambu = $this->makeRambuWithRiwayat($admin);

        $response = $this->get(route('rambu.show', $rambu));

        $response->assertSee('https://www.google.com/maps/search/?api=1&query=-3.30,114.59');
        $response->assertSee(route('peta', ['focus' => $rambu->id]), false);
    }

    public function test_riwayat_pekerjaan_links_to_spk_detail_page(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $rambu = $this->makeRambuWithRiwayat($admin);
        $spk = $rambu->rambuPasang()->first()->spk;

        $response = $this->get(route('rambu.show', $rambu));

        $response->assertSee(route('admin.spk.show', $spk), false);
    }

    public function test_admin_sees_ke_validasi_button_when_rambu_pasang_menunggu_validasi(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $rambu = $this->makeRambuWithRiwayat($admin);
        $rambuPasang = $rambu->rambuPasang()->first();
        $rambuPasang->update(['status' => 'menunggu_validasi']);

        $response = $this->get(route('rambu.show', $rambu));

        $response->assertSee(route('admin.validasi.show', $rambuPasang->spk), false);
        $response->assertSee('Ke Halaman Validasi');
    }

    public function test_rambu_detail_shows_riwayat_temuan_kondisi_with_photo(): void
    {
        $admin = User::factory()->admin()->create();
        $petugas = User::factory()->create();
        $this->actingAs($admin);

        $rambu = $this->makeRambuWithRiwayat($admin);

        LaporanKondisi::create([
            'rambu_id' => $rambu->id,
            'dilaporkan_oleh' => $petugas->id,
            'kondisi_dilaporkan' => 'rusak',
            'foto' => 'laporan-kondisi/contoh-temuan.jpg',
            'catatan' => 'Tiang bengkok kena tabrak.',
        ]);

        $response = $this->get(route('rambu.show', $rambu));

        $response->assertOk();
        $response->assertSee('Riwayat Temuan Kondisi');
        $response->assertSee('laporan-kondisi/contoh-temuan.jpg', false);
        $response->assertSee('Tiang bengkok kena tabrak.');
        $response->assertSee($petugas->name);
    }

    public function test_petugas_does_not_see_ke_validasi_button(): void
    {
        $admin = User::factory()->admin()->create();
        $rambu = $this->makeRambuWithRiwayat($admin);
        $rambuPasang = $rambu->rambuPasang()->first();
        $rambuPasang->update(['status' => 'menunggu_validasi']);

        $this->actingAs(User::factory()->create());

        $response = $this->get(route('rambu.show', $rambu));

        $response->assertDontSee('Ke Halaman Validasi');
    }
}
