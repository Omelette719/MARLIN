<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\Dashboard as DashboardComponent;
use App\Models\JenisRambu;
use App\Models\Rambu;
use App\Models\RambuPasang;
use App\Models\Spk;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
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

    public function test_rambu_terpasang_and_belum_terpasang_cards_link_to_filtered_daftar_rambu(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $response = $this->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSee(route('admin.rambu.index', ['status' => 'terpasang']), false);
        $response->assertSee(route('admin.rambu.index', ['status' => 'belum_terpasang']), false);
    }

    private function makeSpkWithProgress(User $admin, string $nomorSurat, array $spkAttrs, array $statuses): Spk
    {
        $jenisRambu = JenisRambu::create(['nama_jenis' => 'Rambu Peringatan']);

        $spk = Spk::create(array_merge([
            'nomor_surat' => $nomorSurat,
            'dibuat_oleh' => $admin->id,
            'wilayah' => 'Banjarmasin Tengah',
            'deadline' => now()->addDays(10),
            'urgensi' => 'rendah',
            'prioritas' => false,
            'status' => 'aktif',
            'asal_permintaan' => 'internal',
        ], $spkAttrs));

        foreach ($statuses as $status) {
            $rambu = Rambu::create([
                'jenis_rambu_id' => $jenisRambu->id,
                'wilayah' => 'Banjarmasin Tengah',
                'lokasi' => 'Lokasi',
                'koordinat' => '-3.30,114.59',
            ]);

            RambuPasang::create([
                'rambu_spk_id' => $spk->id,
                'rambu_id' => $rambu->id,
                'jenis_pekerjaan' => 'pasang_baru',
                'jumlah' => 1,
                'status' => $status,
            ]);
        }

        return $spk;
    }

    public function test_dashboard_shows_prioritas_spk_before_less_urgent_ones(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $this->makeSpkWithProgress($admin, 'SR-2026/BJM/2001', ['urgensi' => 'rendah'], ['belum', 'belum']);
        $urgent = $this->makeSpkWithProgress($admin, 'SR-2026/BJM/2002', ['prioritas' => true, 'urgensi' => 'tinggi'], ['belum', 'belum']);

        $spkPrioritas = Livewire::test(DashboardComponent::class)->viewData('spkPrioritas');

        $this->assertSame($urgent->id, $spkPrioritas->first()['spk']->id);
        $this->assertTrue($spkPrioritas->first()['butuhPerhatian']);
    }

    public function test_dashboard_orders_non_urgent_spk_by_lowest_progress_first(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $hampirSelesai = $this->makeSpkWithProgress($admin, 'SR-2026/BJM/2003', [], ['selesai', 'belum']);
        $belumMulai = $this->makeSpkWithProgress($admin, 'SR-2026/BJM/2004', [], ['belum', 'belum']);

        $spkPrioritas = Livewire::test(DashboardComponent::class)->viewData('spkPrioritas');
        $urutanId = $spkPrioritas->pluck('spk.id')->values()->all();

        $this->assertSame(
            [$belumMulai->id, $hampirSelesai->id],
            array_values(array_intersect($urutanId, [$belumMulai->id, $hampirSelesai->id]))
        );
    }

    public function test_dashboard_spk_progress_ignores_batal_rambu(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $spk = $this->makeSpkWithProgress($admin, 'SR-2026/BJM/2005', [], ['selesai', 'batal', 'batal']);

        $row = Livewire::test(DashboardComponent::class)->viewData('spkPrioritas')
            ->firstWhere('spk.id', $spk->id);

        $this->assertSame(1, $row['total']);
        $this->assertSame(1, $row['selesai']);
    }

    public function test_dashboard_limits_spk_prioritas_to_five(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        foreach (range(1, 7) as $i) {
            $this->makeSpkWithProgress($admin, "SR-2026/BJM/210{$i}", [], ['belum']);
        }

        $spkPrioritas = Livewire::test(DashboardComponent::class)->viewData('spkPrioritas');

        $this->assertCount(5, $spkPrioritas);
    }
}
