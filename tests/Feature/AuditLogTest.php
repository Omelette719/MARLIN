<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\DikerjakanOleh;
use App\Models\Spk;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
