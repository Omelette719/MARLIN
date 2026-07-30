<?php

namespace Tests\Feature\Admin;

use App\Models\JenisRambu;
use App\Models\Rambu;
use App\Models\RambuPasang;
use App\Models\Spk;
use App\Models\User;
use App\Support\LaporanRambu;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LaporanRambuTest extends TestCase
{
    use RefreshDatabase;

    private function makeRambuPasang(User $admin, JenisRambu $jenisRambu, string $status, string $nomorSurat): RambuPasang
    {
        $rambu = Rambu::create([
            'jenis_rambu_id' => $jenisRambu->id,
            'wilayah' => 'Banjarmasin Tengah',
            'lokasi' => 'Perempatan '.$nomorSurat,
            'koordinat' => '-3.30,114.59',
        ]);

        $spk = Spk::create([
            'nomor_surat' => $nomorSurat,
            'dibuat_oleh' => $admin->id,
            'wilayah' => 'Banjarmasin Tengah',
            'deadline' => now()->addDays(5),
            'urgensi' => 'sedang',
            'status' => 'aktif',
            'asal_permintaan' => 'internal',
        ]);

        return RambuPasang::create([
            'rambu_spk_id' => $spk->id,
            'rambu_id' => $rambu->id,
            'jenis_pekerjaan' => 'pasang_baru',
            'jumlah' => 1,
            'status' => $status,
        ]);
    }

    public function test_petugas_cannot_access_laporan_rambu(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(route('admin.laporan.rambu'))->assertRedirect(route('dashboard'));
    }

    public function test_admin_can_view_laporan_rambu(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $response = $this->get(route('admin.laporan.rambu'));

        $response->assertOk();
        $response->assertSee('Laporan Rambu');
    }

    public function test_admin_can_export_laporan_rambu_pdf(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $response = $this->get(route('admin.laporan.rambu-export'));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_laporan_rambu_with_no_filters_returns_everything(): void
    {
        $admin = User::factory()->admin()->create();
        $jenisA = JenisRambu::create(['nama_jenis' => 'Rambu Peringatan']);
        $jenisB = JenisRambu::create(['nama_jenis' => 'Rambu Larangan']);

        $this->makeRambuPasang($admin, $jenisA, 'belum', 'SR-2026/BJM/1001');
        $this->makeRambuPasang($admin, $jenisB, 'selesai', 'SR-2026/BJM/1002');

        $data = LaporanRambu::build([]);

        $this->assertSame(2, $data['total']);
    }

    public function test_laporan_rambu_can_be_filtered_by_jenis_rambu(): void
    {
        $admin = User::factory()->admin()->create();
        $jenisA = JenisRambu::create(['nama_jenis' => 'Rambu Peringatan']);
        $jenisB = JenisRambu::create(['nama_jenis' => 'Rambu Larangan']);

        $this->makeRambuPasang($admin, $jenisA, 'belum', 'SR-2026/BJM/1003');
        $this->makeRambuPasang($admin, $jenisB, 'selesai', 'SR-2026/BJM/1004');

        $data = LaporanRambu::build(['jenis_rambu_id' => $jenisA->id]);

        $this->assertSame(1, $data['total']);
        $this->assertSame('SR-2026/BJM/1003', $data['items']->first()->spk->nomor_surat);
    }

    public function test_laporan_rambu_can_be_filtered_by_status(): void
    {
        $admin = User::factory()->admin()->create();
        $jenis = JenisRambu::create(['nama_jenis' => 'Rambu Peringatan']);

        $this->makeRambuPasang($admin, $jenis, 'belum', 'SR-2026/BJM/1005');
        $this->makeRambuPasang($admin, $jenis, 'selesai', 'SR-2026/BJM/1006');

        $data = LaporanRambu::build(['status' => 'selesai']);

        $this->assertSame(1, $data['total']);
        $this->assertSame('SR-2026/BJM/1006', $data['items']->first()->spk->nomor_surat);
    }

    public function test_laporan_rambu_can_be_filtered_by_date_range(): void
    {
        $admin = User::factory()->admin()->create();
        $jenis = JenisRambu::create(['nama_jenis' => 'Rambu Peringatan']);

        $lama = $this->makeRambuPasang($admin, $jenis, 'belum', 'SR-2026/BJM/1007');
        $lama->created_at = now()->subYear();
        $lama->save();

        $this->makeRambuPasang($admin, $jenis, 'belum', 'SR-2026/BJM/1008');

        $data = LaporanRambu::build([
            'tanggal_dari' => now()->subDay()->toDateString(),
            'tanggal_sampai' => now()->toDateString(),
        ]);

        $this->assertSame(1, $data['total']);
        $this->assertSame('SR-2026/BJM/1008', $data['items']->first()->spk->nomor_surat);
    }

    public function test_laporan_rambu_per_status_breakdown_counts_correctly(): void
    {
        $admin = User::factory()->admin()->create();
        $jenis = JenisRambu::create(['nama_jenis' => 'Rambu Peringatan']);

        $this->makeRambuPasang($admin, $jenis, 'belum', 'SR-2026/BJM/1009');
        $this->makeRambuPasang($admin, $jenis, 'selesai', 'SR-2026/BJM/1010');
        $this->makeRambuPasang($admin, $jenis, 'selesai', 'SR-2026/BJM/1011');

        $data = LaporanRambu::build([]);

        $this->assertSame(1, $data['perStatus']['belum']);
        $this->assertSame(2, $data['perStatus']['selesai']);
        $this->assertSame(0, $data['perStatus']['batal']);
    }
}
