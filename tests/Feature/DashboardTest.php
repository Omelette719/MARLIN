<?php

namespace Tests\Feature;

use App\Livewire\User\Dashboard as UserDashboardComponent;
use App\Models\JenisRambu;
use App\Models\Rambu;
use App\Models\RambuPasang;
use App\Models\Spk;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_visit_the_dashboard(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('dashboard'));
        $response->assertOk();
    }

    // The dashboard lists ALL active SPKs (not just joined ones — see
    // ALUR-BISNIS.md), so the download button has to be blocked client-side
    // for SPKs the petugas hasn't joined instead of linking straight to a
    // route that would 403 them.
    public function test_petugas_gets_a_toast_instead_of_a_dead_link_for_an_unjoined_spks_download_button(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs(User::factory()->create());

        $jenisRambu = JenisRambu::create(['nama_jenis' => 'Rambu Peringatan']);
        $rambu = Rambu::create([
            'jenis_rambu_id' => $jenisRambu->id,
            'wilayah' => 'Banjarmasin Tengah',
            'lokasi' => 'Perempatan dekat masjid',
            'koordinat' => '-3.3194,114.5908',
        ]);
        $spk = Spk::create([
            'nomor_surat' => 'SR-2026/BJM/0009',
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
        ]);

        Livewire::test(UserDashboardComponent::class)
            ->assertDontSeeHtml('href="'.route('spk.surat-pengantar', $spk).'"')
            ->call('tautanSuratPengantarDitolak')
            ->assertDispatched('toast-show');
    }
}
