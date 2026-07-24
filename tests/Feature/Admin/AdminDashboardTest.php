<?php

namespace Tests\Feature\Admin;

use App\Models\JenisRambu;
use App\Models\Rambu;
use App\Models\Spk;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_petugas_cannot_view_admin_dashboard(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(route('admin.dashboard'))->assertRedirect(route('dashboard'));
    }

    public function test_admin_dashboard_shows_real_counts_not_hardcoded_data(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $jenisRambu = JenisRambu::create(['nama_jenis' => 'Rambu Peringatan']);

        Rambu::create([
            'jenis_rambu_id' => $jenisRambu->id,
            'wilayah' => 'Banjarmasin Tengah',
            'lokasi' => 'Depan pasar',
            'koordinat' => '-3.3194,114.5908',
            'sudah_terpasang' => true,
        ]);

        Rambu::create([
            'jenis_rambu_id' => $jenisRambu->id,
            'wilayah' => 'Banjarmasin Utara',
            'lokasi' => 'Simpang tiga',
            'koordinat' => '-3.29,114.60',
            'sudah_terpasang' => false,
        ]);

        Spk::create([
            'nomor_surat' => 'SR-2026/BJM/9001',
            'dibuat_oleh' => $admin->id,
            'jenis_spk' => 'pasang_baru',
            'wilayah' => 'Banjarmasin Tengah',
            'deadline' => now()->addDays(5),
            'urgensi' => 'sedang',
            'status' => 'aktif',
            'asal_permintaan' => 'internal',
        ]);

        $response = $this->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSee('SR-2026/BJM/9001');
        $response->assertDontSee('DISHUB-BJM');
    }
}
