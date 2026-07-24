<?php

namespace Tests\Feature\Admin;

use App\Models\JenisRambu;
use App\Models\Rambu;
use App\Models\RambuPasang;
use App\Models\Spk;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
