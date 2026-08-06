<?php

namespace Tests\Feature\Admin;

use App\Models\JenisRambu;
use App\Models\Rambu;
use App\Models\RambuPasang;
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

        $response = $this->get(route('admin.laporan.export', [
            'tanggal_dari' => now()->startOfMonth()->toDateString(),
            'tanggal_sampai' => now()->toDateString(),
        ]));

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

        $data = LaporanBulanan::build([
            'tanggal_dari' => now()->startOfMonth()->toDateString(),
            'tanggal_sampai' => now()->toDateString(),
        ]);

        $this->assertSame(2, $data['rambu']['total']);
        $this->assertSame(1, $data['rambu']['terpasang']);
        $this->assertSame(1, $data['rambu']['belum_terpasang']);
        $this->assertSame(2, $data['spk']['dibuat_periode']);
        $this->assertSame(1, $data['spkSelesaiPeriode']->count());
        $this->assertSame(1, $data['spkAktif']->count());
        $this->assertTrue($data['spkSelesaiPeriode']->contains('nomor_surat', 'SR-2026/BJM/9001'));
        $this->assertTrue($data['spkAktif']->contains('nomor_surat', 'SR-2026/BJM/9002'));
    }

    public function test_laporan_bulanan_defaults_to_current_month_when_no_filters_given(): void
    {
        $data = LaporanBulanan::build([]);

        $this->assertSame(now()->startOfMonth()->toDateString(), $data['awal']->toDateString());
        $this->assertSame(now()->toDateString(), $data['akhir']->toDateString());
    }

    public function test_laporan_bulanan_can_be_scoped_by_jenis_rambu(): void
    {
        $jenisA = JenisRambu::create(['nama_jenis' => 'Rambu Peringatan']);
        $jenisB = JenisRambu::create(['nama_jenis' => 'Rambu Larangan']);

        Rambu::create([
            'jenis_rambu_id' => $jenisA->id,
            'wilayah' => 'Banjarmasin Tengah',
            'lokasi' => 'A',
            'koordinat' => '-3.30,114.59',
        ]);
        Rambu::create([
            'jenis_rambu_id' => $jenisB->id,
            'wilayah' => 'Banjarmasin Tengah',
            'lokasi' => 'B',
            'koordinat' => '-3.31,114.60',
        ]);

        $data = LaporanBulanan::build([
            'tanggal_dari' => now()->startOfMonth()->toDateString(),
            'tanggal_sampai' => now()->toDateString(),
            'jenis_rambu_id' => $jenisA->id,
        ]);

        $this->assertSame(1, $data['rambu']['total']);
    }

    public function test_laporan_bulanan_rambu_detail_can_be_scoped_by_status(): void
    {
        $admin = User::factory()->admin()->create();
        $jenisRambu = JenisRambu::create(['nama_jenis' => 'Rambu Peringatan']);
        $rambu = Rambu::create([
            'jenis_rambu_id' => $jenisRambu->id,
            'wilayah' => 'Banjarmasin Tengah',
            'lokasi' => 'A',
            'koordinat' => '-3.30,114.59',
        ]);
        $spk = Spk::create([
            'nomor_surat' => 'SR-2026/BJM/9003',
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
            'status' => 'selesai',
        ]);

        $data = LaporanBulanan::build([
            'tanggal_dari' => now()->startOfMonth()->toDateString(),
            'tanggal_sampai' => now()->toDateString(),
            'status' => 'tertunda',
        ]);

        $this->assertSame(0, $data['rambuDetail']['total']);

        $data = LaporanBulanan::build([
            'tanggal_dari' => now()->startOfMonth()->toDateString(),
            'tanggal_sampai' => now()->toDateString(),
            'status' => 'selesai',
        ]);

        $this->assertSame(1, $data['rambuDetail']['total']);
    }

    public function test_laporan_bulanan_computes_average_duration_and_deadline_delta(): void
    {
        $admin = User::factory()->admin()->create();

        // Finished 10 days after creation, 12 days before its deadline.
        $spk1 = Spk::create([
            'nomor_surat' => 'SR-2026/BJM/9101',
            'dibuat_oleh' => $admin->id,
            'wilayah' => 'Banjarmasin Tengah',
            'deadline' => now()->addDays(10),
            'urgensi' => 'sedang',
            'status' => 'selesai',
            'asal_permintaan' => 'internal',
        ]);
        $spk1->created_at = now()->subDays(12);
        $spk1->selesai_pada = now()->subDays(2);
        $spk1->save();

        // Finished 10 days after creation, 3 days after (late for) its deadline.
        $spk2 = Spk::create([
            'nomor_surat' => 'SR-2026/BJM/9102',
            'dibuat_oleh' => $admin->id,
            'wilayah' => 'Banjarmasin Tengah',
            'deadline' => now()->subDays(3),
            'urgensi' => 'sedang',
            'status' => 'selesai',
            'asal_permintaan' => 'internal',
        ]);
        $spk2->created_at = now()->subDays(10);
        $spk2->selesai_pada = now();
        $spk2->save();

        $data = LaporanBulanan::build([
            'tanggal_dari' => now()->startOfMonth()->toDateString(),
            'tanggal_sampai' => now()->toDateString(),
        ]);

        $this->assertSame(2, $data['spkSelesaiPeriode']->count());
        $this->assertSame(10.0, $data['analitikSelesai']['rata_rata_durasi_hari']);
        $this->assertSame(-4.5, $data['analitikSelesai']['rata_rata_selisih_deadline_hari']);
        $this->assertSame(1, $data['analitikSelesai']['tepat_waktu_count']);
        $this->assertSame(1, $data['analitikSelesai']['terlambat_count']);
    }

    public function test_laporan_bulanan_analitik_is_null_when_no_spk_selesai(): void
    {
        $data = LaporanBulanan::build([
            'tanggal_dari' => now()->startOfMonth()->toDateString(),
            'tanggal_sampai' => now()->toDateString(),
        ]);

        $this->assertSame(0, $data['spkSelesaiPeriode']->count());
        $this->assertNull($data['analitikSelesai']['rata_rata_durasi_hari']);
        $this->assertNull($data['analitikSelesai']['rata_rata_selisih_deadline_hari']);
        $this->assertSame(0, $data['analitikSelesai']['tepat_waktu_count']);
        $this->assertSame(0, $data['analitikSelesai']['terlambat_count']);
    }
}
