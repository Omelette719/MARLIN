<?php

namespace Tests\Feature\User;

use App\Livewire\User\RiwayatSpk as RiwayatSpkComponent;
use App\Models\DikerjakanOleh;
use App\Models\JenisRambu;
use App\Models\Rambu;
use App\Models\Spk;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RiwayatSpkTest extends TestCase
{
    use RefreshDatabase;

    private function makeSpk(User $admin, string $nomor, string $status = 'aktif'): Spk
    {
        $jenisRambu = JenisRambu::firstOrCreate(['nama_jenis' => 'Rambu Peringatan']);

        $rambu = Rambu::create([
            'jenis_rambu_id' => $jenisRambu->id,
            'wilayah' => 'Banjarmasin Tengah',
            'lokasi' => 'Lokasi '.$nomor,
            'koordinat' => '-3.30,114.59',
        ]);

        $spk = Spk::create([
            'nomor_surat' => $nomor,
            'dibuat_oleh' => $admin->id,
            'wilayah' => 'Banjarmasin Tengah',
            'deadline' => now()->addDays(5),
            'urgensi' => 'sedang',
            'status' => 'aktif',
            'asal_permintaan' => 'internal',
        ]);

        if ($status !== 'aktif') {
            $spk->update(['status' => $status]);
        }

        return $spk;
    }

    public function test_admin_cannot_access_riwayat_spk(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $this->get(route('user.riwayat-spk'))->assertRedirect(route('admin.dashboard'));
    }

    public function test_petugas_sees_completed_spk_in_riwayat(): void
    {
        $admin = User::factory()->admin()->create();
        $petugas = User::factory()->create();
        $this->actingAs($petugas);

        $spk = $this->makeSpk($admin, 'SR-2026/BJM/7101', status: 'selesai');

        DikerjakanOleh::create([
            'by_spk_id' => $spk->id,
            'by_user_id' => $petugas->id,
            'is_perwakilan' => true,
        ]);

        $response = $this->get(route('user.riwayat-spk'));

        $response->assertOk();
        $response->assertSee('SR-2026/BJM/7101');
        $response->assertSee('Selesai');
    }

    public function test_riwayat_shows_full_history_by_default_regardless_of_when_assigned(): void
    {
        $admin = User::factory()->admin()->create();
        $petugas = User::factory()->create();
        $this->actingAs($petugas);

        $spkBaru = $this->makeSpk($admin, 'SR-2026/BJM/7102');
        $spkLama = $this->makeSpk($admin, 'SR-2026/BJM/7103');

        DikerjakanOleh::create([
            'by_spk_id' => $spkBaru->id,
            'by_user_id' => $petugas->id,
            'is_perwakilan' => true,
        ]);

        $assignmentLama = DikerjakanOleh::create([
            'by_spk_id' => $spkLama->id,
            'by_user_id' => $petugas->id,
            'is_perwakilan' => true,
        ]);
        $assignmentLama->created_at = now()->subYear();
        $assignmentLama->save();

        $response = $this->get(route('user.riwayat-spk'));

        $response->assertOk();
        $response->assertSee('SR-2026/BJM/7102');
        $response->assertSee('SR-2026/BJM/7103');
    }

    public function test_riwayat_can_be_filtered_by_tanggal_range(): void
    {
        $admin = User::factory()->admin()->create();
        $petugas = User::factory()->create();
        $this->actingAs($petugas);

        $spkBulanIni = $this->makeSpk($admin, 'SR-2026/BJM/7102');
        $spkBulanLalu = $this->makeSpk($admin, 'SR-2026/BJM/7103');

        DikerjakanOleh::create([
            'by_spk_id' => $spkBulanIni->id,
            'by_user_id' => $petugas->id,
            'is_perwakilan' => true,
        ]);

        $assignmentBulanLalu = DikerjakanOleh::create([
            'by_spk_id' => $spkBulanLalu->id,
            'by_user_id' => $petugas->id,
            'is_perwakilan' => true,
        ]);
        $assignmentBulanLalu->created_at = now()->subMonthNoOverflow();
        $assignmentBulanLalu->save();

        $response = $this->get(route('user.riwayat-spk', [
            'tanggal_dari' => now()->startOfMonth()->toDateString(),
            'tanggal_sampai' => now()->endOfMonth()->toDateString(),
        ]));

        $response->assertOk();
        $response->assertSee('SR-2026/BJM/7102');
        $response->assertDontSee('SR-2026/BJM/7103');
    }

    public function test_petugas_does_not_see_spk_they_never_worked_on(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs(User::factory()->create());

        $this->makeSpk($admin, 'SR-2026/BJM/7104');

        Livewire::test(RiwayatSpkComponent::class)
            ->assertSee('Tidak ada surat');
    }
}
