<?php

namespace Tests\Feature\Admin;

use App\Models\JenisRambu;
use App\Models\Rambu;
use App\Models\Spk;
use App\Models\User;
use App\Support\LaporanBulanan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LaporanBulananTest extends TestCase
{
    use RefreshDatabase;

    public function test_petugas_cannot_access_laporan_bulanan(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(route('admin.laporan.index'))->assertRedirect(route('dashboard'));
    }

    public function test_admin_can_view_laporan_bulanan(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $response = $this->get(route('admin.laporan.index'));

        $response->assertOk();
        $response->assertSee('Laporan Bulanan');
    }

    public function test_admin_can_export_laporan_bulanan_pdf(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $response = $this->get(route('admin.laporan.export', ['bulan' => now()->format('Y-m')]));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_laporan_bulanan_counts_rambu_and_spk_correctly(): void
    {
        $admin = User::factory()->admin()->create();
        $jenisRambu = JenisRambu::create(['nama_jenis' => 'Rambu Peringatan']);

        Rambu::create([
            'jenis_rambu_id' => $jenisRambu->id,
            'wilayah' => 'Banjarmasin Tengah',
            'lokasi' => 'A',
            'koordinat' => '-3.30,114.59',
            'sudah_terpasang' => true,
            'kondisi_terkini' => 'baik',
        ]);
        Rambu::create([
            'jenis_rambu_id' => $jenisRambu->id,
            'wilayah' => 'Banjarmasin Tengah',
            'lokasi' => 'B',
            'koordinat' => '-3.31,114.60',
            'sudah_terpasang' => false,
            'kondisi_terkini' => 'baik',
        ]);

        $bulanIni = now()->format('Y-m');

        $spkSelesai = Spk::create([
            'nomor_surat' => 'SR-2026/BJM/9001',
            'dibuat_oleh' => $admin->id,
            'wilayah' => 'Banjarmasin Tengah',
            'deadline' => now()->addDays(5),
            'urgensi' => 'sedang',
            'status' => 'aktif',
            'asal_permintaan' => 'internal',
        ]);
        $spkSelesai->update(['status' => 'selesai']);

        Spk::create([
            'nomor_surat' => 'SR-2026/BJM/9002',
            'dibuat_oleh' => $admin->id,
            'wilayah' => 'Banjarmasin Selatan',
            'deadline' => now()->addDays(5),
            'urgensi' => 'sedang',
            'status' => 'aktif',
            'asal_permintaan' => 'internal',
        ]);

        $data = LaporanBulanan::build($bulanIni);

        $this->assertSame(2, $data['rambu']['total']);
        $this->assertSame(1, $data['rambu']['terpasang']);
        $this->assertSame(1, $data['rambu']['belum_terpasang']);
        $this->assertSame(2, $data['spk']['dibuat_bulan_ini']);
        $this->assertSame(1, $data['spkSelesaiBulanIni']->count());
        $this->assertSame(1, $data['spkAktif']->count());
        $this->assertTrue($data['spkSelesaiBulanIni']->contains('nomor_surat', 'SR-2026/BJM/9001'));
        $this->assertTrue($data['spkAktif']->contains('nomor_surat', 'SR-2026/BJM/9002'));
    }
}
