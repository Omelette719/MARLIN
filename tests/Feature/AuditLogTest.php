<?php

namespace Tests\Feature;

use App\Livewire\AuditLog as AuditLogComponent;
use App\Models\AuditLog;
use App\Models\DikerjakanOleh;
use App\Models\Spk;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    private function makeSpk(User $admin, string $nomor): Spk
    {
        return Spk::create([
            'nomor_surat' => $nomor,
            'dibuat_oleh' => $admin->id,
            'wilayah' => 'Banjarmasin Tengah',
            'deadline' => now()->addDays(5),
            'urgensi' => 'sedang',
            'status' => 'aktif',
            'asal_permintaan' => 'internal',
        ]);
    }

    public function test_admin_sees_all_audit_log_entries(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $spkA = $this->makeSpk($admin, 'SR-2026/BJM/A');
        $spkB = $this->makeSpk($admin, 'SR-2026/BJM/B');

        AuditLog::create(['user_id' => $admin->id, 'spk_id' => $spkA->id, 'aksi' => 'spk_dibuat']);
        AuditLog::create(['user_id' => $admin->id, 'spk_id' => $spkB->id, 'aksi' => 'spk_dibuat']);

        $response = $this->get(route('audit-log'));
        $response->assertOk();
        $response->assertSee('SR-2026/BJM/A');
        $response->assertSee('SR-2026/BJM/B');
    }

    public function test_petugas_only_sees_own_audit_log_entries(): void
    {
        $admin = User::factory()->admin()->create();
        $petugas = User::factory()->create();
        $this->actingAs($petugas);

        $joined = $this->makeSpk($admin, 'SR-2026/BJM/JOINED');

        DikerjakanOleh::create(['by_spk_id' => $joined->id, 'by_user_id' => $petugas->id, 'is_perwakilan' => false]);

        AuditLog::create(['user_id' => $petugas->id, 'spk_id' => $joined->id, 'aksi' => 'laporan_dikirim']);
        AuditLog::create(['user_id' => $admin->id, 'spk_id' => $joined->id, 'aksi' => 'spk_dibuat']);

        $response = $this->get(route('audit-log'));
        $response->assertOk();
        $response->assertSee('laporan_dikirim');
        $response->assertDontSee('spk_dibuat');
    }

    public function test_admin_can_filter_by_pengguna(): void
    {
        $admin = User::factory()->admin()->create();
        $petugas = User::factory()->create();
        $this->actingAs($admin);

        $spk = $this->makeSpk($admin, 'SR-2026/BJM/FILTER');

        // Distinct "keterangan" text, not "aksi" — the aksi dropdown lists
        // every distinct aksi value regardless of the pengguna filter (it's
        // meant to always show every possible option), so asserting on the
        // aksi string itself would pass/fail for the wrong reason.
        AuditLog::create(['user_id' => $admin->id, 'spk_id' => $spk->id, 'aksi' => 'spk_dibuat', 'keterangan' => 'Ditulis oleh admin']);
        AuditLog::create(['user_id' => $petugas->id, 'spk_id' => $spk->id, 'aksi' => 'laporan_dikirim', 'keterangan' => 'Ditulis oleh petugas']);

        Livewire::test(AuditLogComponent::class)
            ->set('pengguna', (string) $petugas->id)
            ->assertSee('Ditulis oleh petugas')
            ->assertDontSee('Ditulis oleh admin');
    }

    public function test_petugas_does_not_see_pengguna_filter(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get(route('audit-log'));

        $response->assertOk();
        $response->assertDontSee('Semua Pengguna');
    }

    public function test_admin_can_filter_by_date_range(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $spk = $this->makeSpk($admin, 'SR-2026/BJM/TANGGAL');

        $lama = AuditLog::create(['user_id' => $admin->id, 'spk_id' => $spk->id, 'aksi' => 'spk_dibuat', 'keterangan' => 'Catatan lama']);
        $lama->created_at = now()->subDays(10);
        $lama->save();

        $baru = AuditLog::create(['user_id' => $admin->id, 'spk_id' => $spk->id, 'aksi' => 'spk_diedit', 'keterangan' => 'Catatan baru']);
        $baru->created_at = now();
        $baru->save();

        Livewire::test(AuditLogComponent::class)
            ->set('tanggal_dari', now()->subDays(2)->toDateString())
            ->assertSee('Catatan baru')
            ->assertDontSee('Catatan lama');
    }
}
