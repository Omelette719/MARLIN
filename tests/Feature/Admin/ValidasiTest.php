<?php

namespace Tests\Feature\Admin;

use App\Enums\StatusLaporan;
use App\Enums\StatusRambuPasang;
use App\Enums\StatusSpk;
use App\Livewire\Admin\Validasi\Show as ValidasiShowComponent;
use App\Models\AuditLog;
use App\Models\JenisRambu;
use App\Models\Kendala;
use App\Models\LaporanPengerjaan;
use App\Models\Notifikasi;
use App\Models\Rambu;
use App\Models\RambuPasang;
use App\Models\Spk;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ValidasiTest extends TestCase
{
    use RefreshDatabase;

    private function makeSpkWithPendingLaporan(User $admin, User $petugas, string $jenisPekerjaan = 'pasang_baru'): array
    {
        $jenisRambu = JenisRambu::create(['nama_jenis' => 'Rambu Peringatan']);

        $rambu = Rambu::create([
            'jenis_rambu_id' => $jenisRambu->id,
            'wilayah' => 'Banjarmasin Tengah',
            'lokasi' => 'Perempatan dekat masjid',
            'koordinat' => '-3.3194,114.5908',
            'kondisi_terkini' => $jenisPekerjaan === 'perbaikan' ? 'rusak' : 'baik',
            'sudah_terpasang' => $jenisPekerjaan === 'perbaikan',
        ]);

        $spk = Spk::create([
            'nomor_surat' => 'SR-2026/BJM/0002',
            'dibuat_oleh' => $admin->id,
            'wilayah' => 'Banjarmasin Tengah',
            'deadline' => now()->addDays(5),
            'urgensi' => 'sedang',
            'status' => 'aktif',
            'asal_permintaan' => 'internal',
            'laporan_akhir_diajukan_at' => now(),
        ]);

        $rambuPasang = RambuPasang::create([
            'rambu_spk_id' => $spk->id,
            'rambu_id' => $rambu->id,
            'jenis_pekerjaan' => $jenisPekerjaan,
            'jumlah' => 1,
            'status' => 'menunggu_validasi',
        ]);

        $laporan = LaporanPengerjaan::create([
            'rambu_pasang_id' => $rambuPasang->id,
            'dilaporkan_oleh' => $petugas->id,
            'status' => 'diajukan',
        ]);

        return compact('spk', 'rambu', 'rambuPasang', 'laporan');
    }

    public function test_admin_can_view_daftar_validasi(): void
    {
        $admin = User::factory()->admin()->create();
        $petugas = User::factory()->create();
        $this->actingAs($admin);

        ['spk' => $spk] = $this->makeSpkWithPendingLaporan($admin, $petugas);

        $response = $this->get(route('admin.validasi.index'));
        $response->assertOk();
        $response->assertSee($spk->nomor_surat);
    }

    public function test_spk_without_laporan_akhir_diajukan_not_shown_in_daftar_validasi(): void
    {
        $admin = User::factory()->admin()->create();
        $petugas = User::factory()->create();
        $this->actingAs($admin);

        ['spk' => $spk] = $this->makeSpkWithPendingLaporan($admin, $petugas);
        $spk->update(['laporan_akhir_diajukan_at' => null]);

        $response = $this->get(route('admin.validasi.index'));
        $response->assertOk();
        $response->assertDontSee($spk->nomor_surat);
    }

    public function test_admin_can_approve_laporan_pasang_baru(): void
    {
        $admin = User::factory()->admin()->create();
        $petugas = User::factory()->create();
        $this->actingAs($admin);

        ['spk' => $spk, 'rambu' => $rambu, 'rambuPasang' => $rambuPasang, 'laporan' => $laporan] =
            $this->makeSpkWithPendingLaporan($admin, $petugas, 'pasang_baru');

        Livewire::test(ValidasiShowComponent::class, ['spk' => $spk])
            ->set("checked.{$rambuPasang->id}", true)
            ->call('lanjutkan');

        $this->assertSame(StatusLaporan::Diterima, $laporan->fresh()->status);
        $this->assertSame(StatusRambuPasang::Selesai, $rambuPasang->fresh()->status);
        $this->assertTrue($rambu->fresh()->sudah_terpasang);
        $this->assertSame(StatusSpk::Selesai, $spk->fresh()->status);
        $this->assertNull($spk->fresh()->laporan_akhir_diajukan_at);
        $this->assertSame(1, AuditLog::where('aksi', 'validasi_diterima')->count());
        $this->assertSame(1, Notifikasi::where('user_id', $petugas->id)->count());
    }

    public function test_admin_can_approve_laporan_perbaikan_and_fixes_kondisi(): void
    {
        $admin = User::factory()->admin()->create();
        $petugas = User::factory()->create();
        $this->actingAs($admin);

        ['rambu' => $rambu, 'rambuPasang' => $rambuPasang, 'laporan' => $laporan, 'spk' => $spk] =
            $this->makeSpkWithPendingLaporan($admin, $petugas, 'perbaikan');

        Livewire::test(ValidasiShowComponent::class, ['spk' => $spk])
            ->set("checked.{$rambuPasang->id}", true)
            ->call('lanjutkan');

        $this->assertSame('baik', $rambu->fresh()->kondisi_terkini->value);
        $this->assertSame(StatusRambuPasang::Selesai, $rambuPasang->fresh()->status);
    }

    public function test_admin_can_reject_laporan_and_spk_stays_aktif(): void
    {
        $admin = User::factory()->admin()->create();
        $petugas = User::factory()->create();
        $this->actingAs($admin);

        ['spk' => $spk, 'rambuPasang' => $rambuPasang, 'laporan' => $laporan] =
            $this->makeSpkWithPendingLaporan($admin, $petugas);

        Livewire::test(ValidasiShowComponent::class, ['spk' => $spk])
            ->set("checked.{$rambuPasang->id}", false)
            ->call('lanjutkan')
            ->assertSet('showPenolakanForm', true)
            ->set("catatanPenolakan.{$rambuPasang->id}", 'Pemasangan miring, perlu diperbaiki.')
            ->call('konfirmasiPenolakan')
            ->assertHasNoErrors();

        $laporan->refresh();
        $this->assertSame(StatusLaporan::Ditolak, $laporan->status);
        $this->assertSame('Pemasangan miring, perlu diperbaiki.', $laporan->catatan_penolakan);
        $this->assertSame(StatusRambuPasang::Revisi, $rambuPasang->fresh()->status);
        $this->assertSame(StatusSpk::Aktif, $spk->fresh()->status);
        $this->assertNull($spk->fresh()->laporan_akhir_diajukan_at);
        $this->assertSame(1, AuditLog::where('aksi', 'validasi_ditolak')->count());
        $this->assertSame(1, Notifikasi::where('user_id', $petugas->id)->count());
    }

    public function test_validasi_page_shows_before_photo_from_rambu_pasang_and_after_photo_from_laporan(): void
    {
        $admin = User::factory()->admin()->create();
        $petugas = User::factory()->create();
        $this->actingAs($admin);

        ['spk' => $spk, 'rambuPasang' => $rambuPasang, 'laporan' => $laporan] =
            $this->makeSpkWithPendingLaporan($admin, $petugas);

        $rambuPasang->update(['foto_survei' => 'rambu-pasang/survei/contoh-sebelum.jpg']);
        $laporan->update(['foto_sesudah' => 'laporan-pengerjaan/sesudah/contoh-sesudah.jpg']);

        $response = $this->get(route('admin.validasi.show', $spk));

        $response->assertOk();
        $response->assertSee('rambu-pasang/survei/contoh-sebelum.jpg', false);
        $response->assertSee('laporan-pengerjaan/sesudah/contoh-sesudah.jpg', false);
    }

    public function test_rejection_requires_catatan(): void
    {
        $admin = User::factory()->admin()->create();
        $petugas = User::factory()->create();
        $this->actingAs($admin);

        ['spk' => $spk, 'rambuPasang' => $rambuPasang, 'laporan' => $laporan] = $this->makeSpkWithPendingLaporan($admin, $petugas);

        Livewire::test(ValidasiShowComponent::class, ['spk' => $spk])
            ->set("checked.{$rambuPasang->id}", false)
            ->call('lanjutkan')
            ->set("catatanPenolakan.{$rambuPasang->id}", '')
            ->call('konfirmasiPenolakan')
            ->assertHasErrors(["catatanPenolakan.{$rambuPasang->id}"]);

        $this->assertSame(StatusLaporan::Diajukan, $laporan->fresh()->status);
    }

    public function test_validasi_includes_kendala_flagged_rambu_and_can_approve_it(): void
    {
        $admin = User::factory()->admin()->create();
        $petugas = User::factory()->create();
        $this->actingAs($admin);

        $jenisRambu = JenisRambu::create(['nama_jenis' => 'Rambu Larangan']);
        $rambu = Rambu::create([
            'jenis_rambu_id' => $jenisRambu->id,
            'wilayah' => 'Banjarmasin Utara',
            'lokasi' => 'Depan pasar',
            'koordinat' => '-3.29,114.60',
        ]);

        $spk = Spk::create([
            'nomor_surat' => 'SR-2026/BJM/0099',
            'dibuat_oleh' => $admin->id,
            'wilayah' => 'Banjarmasin Utara',
            'deadline' => now()->addDays(5),
            'urgensi' => 'sedang',
            'status' => 'aktif',
            'asal_permintaan' => 'internal',
            'laporan_akhir_diajukan_at' => now(),
        ]);

        $rambuPasang = RambuPasang::create([
            'rambu_spk_id' => $spk->id,
            'rambu_id' => $rambu->id,
            'jenis_pekerjaan' => 'pasang_baru',
            'jumlah' => 1,
            'status' => 'tertunda',
        ]);

        Kendala::create([
            'rambu_pasang_id' => $rambuPasang->id,
            'dilaporkan_oleh' => $petugas->id,
            'alasan' => 'Akses jalan tertutup proyek lain.',
            'foto' => 'kendala/contoh.jpg',
        ]);

        $response = $this->get(route('admin.validasi.show', $spk));
        $response->assertOk();
        $response->assertSee('Akses jalan tertutup proyek lain.');
        $response->assertSee('kendala/contoh.jpg', false);

        Livewire::test(ValidasiShowComponent::class, ['spk' => $spk])
            ->set("checked.{$rambuPasang->id}", true)
            ->call('lanjutkan');

        $this->assertSame(StatusRambuPasang::Selesai, $rambuPasang->fresh()->status);
        $this->assertTrue($rambu->fresh()->sudah_terpasang);
        $this->assertSame(1, Notifikasi::where('user_id', $petugas->id)->count());
    }

    public function test_admin_can_reject_kendala_flagged_rambu_back_to_revisi(): void
    {
        $admin = User::factory()->admin()->create();
        $petugas = User::factory()->create();
        $this->actingAs($admin);

        $jenisRambu = JenisRambu::create(['nama_jenis' => 'Rambu Larangan']);
        $rambu = Rambu::create([
            'jenis_rambu_id' => $jenisRambu->id,
            'wilayah' => 'Banjarmasin Utara',
            'lokasi' => 'Depan pasar',
            'koordinat' => '-3.29,114.60',
        ]);

        $spk = Spk::create([
            'nomor_surat' => 'SR-2026/BJM/0098',
            'dibuat_oleh' => $admin->id,
            'wilayah' => 'Banjarmasin Utara',
            'deadline' => now()->addDays(5),
            'urgensi' => 'sedang',
            'status' => 'aktif',
            'asal_permintaan' => 'internal',
            'laporan_akhir_diajukan_at' => now(),
        ]);

        $rambuPasang = RambuPasang::create([
            'rambu_spk_id' => $spk->id,
            'rambu_id' => $rambu->id,
            'jenis_pekerjaan' => 'pasang_baru',
            'jumlah' => 1,
            'status' => 'tertunda',
        ]);

        Kendala::create([
            'rambu_pasang_id' => $rambuPasang->id,
            'dilaporkan_oleh' => $petugas->id,
            'alasan' => 'Akses jalan tertutup proyek lain.',
            'foto' => 'kendala/contoh.jpg',
        ]);

        Livewire::test(ValidasiShowComponent::class, ['spk' => $spk])
            ->set("checked.{$rambuPasang->id}", false)
            ->call('lanjutkan')
            ->set("catatanPenolakan.{$rambuPasang->id}", 'Silakan koordinasi ulang dengan pihak proyek lain.')
            ->call('konfirmasiPenolakan')
            ->assertHasNoErrors();

        $this->assertSame(StatusRambuPasang::Revisi, $rambuPasang->fresh()->status);
        $this->assertSame(StatusSpk::Aktif, $spk->fresh()->status);
        $this->assertNull($spk->fresh()->laporan_akhir_diajukan_at);
    }
}
