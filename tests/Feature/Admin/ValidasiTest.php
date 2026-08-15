<?php

namespace Tests\Feature\Admin;

use App\Enums\StatusLaporan;
use App\Enums\StatusRambuPasang;
use App\Enums\StatusSpk;
use App\Livewire\Admin\Validasi\Show as ValidasiShowComponent;
use App\Models\AuditLog;
use App\Models\DikerjakanOleh;
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
        $this->assertNotNull($spk->fresh()->selesai_pada);
        $this->assertNull($spk->fresh()->laporan_akhir_diajukan_at);
        $this->assertSame(1, AuditLog::where('aksi', 'validasi_diterima')->count());
        $this->assertSame(1, Notifikasi::where('user_id', $petugas->id)->count());
        $this->assertSame(route('user.spk.show', $spk), Notifikasi::where('user_id', $petugas->id)->first()->url);
    }

    public function test_finalizing_validasi_dispatches_go_back_instead_of_hard_redirect(): void
    {
        // This page is reachable from more than the validasi queue (a rambu
        // detail page, a notification link), so finalize() defers to the
        // client-side marlinGoBack() helper (which prefers real browser
        // back-navigation) instead of always redirecting to the index.
        $admin = User::factory()->admin()->create();
        $petugas = User::factory()->create();
        $this->actingAs($admin);

        ['spk' => $spk, 'rambuPasang' => $rambuPasang] =
            $this->makeSpkWithPendingLaporan($admin, $petugas, 'pasang_baru');

        Livewire::test(ValidasiShowComponent::class, ['spk' => $spk])
            ->set("checked.{$rambuPasang->id}", true)
            ->call('lanjutkan')
            ->assertDispatched('marlin-go-back', fallback: route('admin.validasi.index'))
            ->assertNoRedirect();
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
        $this->assertSame(route('user.spk.show', $spk), Notifikasi::where('user_id', $petugas->id)->first()->url);
    }

    // With two rambu in one SPK, one accepted and one rejected in the same
    // batch, the notification text used to only say "SPK X diterima" / "SPK
    // X ditolak" for both — identical wording pointing at the same SPK, with
    // no way to tell which rambu each one was actually about.
    public function test_notifications_name_the_specific_rambu_when_one_spk_has_a_mixed_outcome(): void
    {
        $admin = User::factory()->admin()->create();
        $petugas = User::factory()->create();
        $this->actingAs($admin);

        $jenisRambu = JenisRambu::create(['nama_jenis' => 'Rambu Peringatan']);

        $spk = Spk::create([
            'nomor_surat' => 'SR-2026/BJM/0099',
            'dibuat_oleh' => $admin->id,
            'wilayah' => 'Banjarmasin Tengah',
            'deadline' => now()->addDays(5),
            'urgensi' => 'sedang',
            'status' => 'aktif',
            'asal_permintaan' => 'internal',
            'laporan_akhir_diajukan_at' => now(),
        ]);

        $rambuDiterima = Rambu::create([
            'jenis_rambu_id' => $jenisRambu->id,
            'wilayah' => 'Banjarmasin Tengah',
            'lokasi' => 'Depan pasar lama',
            'koordinat' => '-3.30,114.59',
        ]);
        $rpDiterima = RambuPasang::create([
            'rambu_spk_id' => $spk->id,
            'rambu_id' => $rambuDiterima->id,
            'jenis_pekerjaan' => 'pasang_baru',
            'jumlah' => 1,
            'status' => 'menunggu_validasi',
        ]);
        LaporanPengerjaan::create(['rambu_pasang_id' => $rpDiterima->id, 'dilaporkan_oleh' => $petugas->id, 'status' => 'diajukan']);

        $rambuDitolak = Rambu::create([
            'jenis_rambu_id' => $jenisRambu->id,
            'wilayah' => 'Banjarmasin Selatan',
            'lokasi' => 'Simpang tiga sekolah',
            'koordinat' => '-3.34,114.59',
        ]);
        $rpDitolak = RambuPasang::create([
            'rambu_spk_id' => $spk->id,
            'rambu_id' => $rambuDitolak->id,
            'jenis_pekerjaan' => 'pasang_baru',
            'jumlah' => 1,
            'status' => 'menunggu_validasi',
        ]);
        LaporanPengerjaan::create(['rambu_pasang_id' => $rpDitolak->id, 'dilaporkan_oleh' => $petugas->id, 'status' => 'diajukan']);

        Livewire::test(ValidasiShowComponent::class, ['spk' => $spk])
            ->set("checked.{$rpDiterima->id}", true)
            ->set("checked.{$rpDitolak->id}", false)
            ->call('lanjutkan')
            ->set("catatanPenolakan.{$rpDitolak->id}", 'Posisi rambu terbalik.')
            ->call('konfirmasiPenolakan')
            ->assertHasNoErrors();

        $diterimaNotif = Notifikasi::where('judul', 'Laporan Diterima')->first();
        $ditolakNotif = Notifikasi::where('judul', 'Laporan Ditolak')->first();

        $this->assertNotNull($diterimaNotif);
        $this->assertNotNull($ditolakNotif);
        $this->assertStringContainsString('Depan pasar lama', $diterimaNotif->pesan);
        $this->assertStringContainsString('Simpang tiga sekolah', $ditolakNotif->pesan);
        // Neither message should be generic enough to be confused for the other.
        $this->assertStringNotContainsString('Simpang tiga sekolah', $diterimaNotif->pesan);
        $this->assertStringNotContainsString('Depan pasar lama', $ditolakNotif->pesan);
    }

    public function test_admin_can_extend_deadline_while_rejecting_laporan(): void
    {
        $admin = User::factory()->admin()->create();
        $petugas = User::factory()->create();
        $this->actingAs($admin);

        ['spk' => $spk, 'rambuPasang' => $rambuPasang] =
            $this->makeSpkWithPendingLaporan($admin, $petugas);

        DikerjakanOleh::create([
            'by_spk_id' => $spk->id,
            'by_user_id' => $petugas->id,
            'is_perwakilan' => true,
        ]);

        $deadlineLama = $spk->deadline->toDateString();
        $deadlineBaru = now()->addDays(15)->toDateString();

        Livewire::test(ValidasiShowComponent::class, ['spk' => $spk])
            ->set("checked.{$rambuPasang->id}", false)
            ->call('lanjutkan')
            ->assertSet('deadlineBaru', $deadlineLama)
            ->set('ubahDeadline', true)
            ->set('deadlineBaru', $deadlineBaru)
            ->set("catatanPenolakan.{$rambuPasang->id}", 'Butuh waktu tambahan untuk revisi.')
            ->call('konfirmasiPenolakan')
            ->assertHasNoErrors();

        $spk->refresh();
        $this->assertSame($deadlineBaru, $spk->deadline->toDateString());
        $this->assertSame($deadlineBaru, $spk->deadline_asli->toDateString());
        $this->assertSame(1, AuditLog::where('aksi', 'deadline_diperpanjang')->count());
        $this->assertSame(
            1,
            Notifikasi::where('user_id', $petugas->id)->where('judul', 'Deadline SPK Diperpanjang')->count()
        );
    }

    public function test_deadline_unchanged_when_ubah_deadline_not_checked(): void
    {
        $admin = User::factory()->admin()->create();
        $petugas = User::factory()->create();
        $this->actingAs($admin);

        ['spk' => $spk, 'rambuPasang' => $rambuPasang] =
            $this->makeSpkWithPendingLaporan($admin, $petugas);

        $deadlineLama = $spk->deadline->toDateString();

        Livewire::test(ValidasiShowComponent::class, ['spk' => $spk])
            ->set("checked.{$rambuPasang->id}", false)
            ->call('lanjutkan')
            ->set('deadlineBaru', now()->addDays(20)->toDateString())
            ->set("catatanPenolakan.{$rambuPasang->id}", 'Perlu revisi.')
            ->call('konfirmasiPenolakan')
            ->assertHasNoErrors();

        $this->assertSame($deadlineLama, $spk->fresh()->deadline->toDateString());
        $this->assertSame(0, AuditLog::where('aksi', 'deadline_diperpanjang')->count());
    }

    public function test_deadline_baru_must_be_a_valid_future_date(): void
    {
        $admin = User::factory()->admin()->create();
        $petugas = User::factory()->create();
        $this->actingAs($admin);

        ['spk' => $spk, 'rambuPasang' => $rambuPasang] =
            $this->makeSpkWithPendingLaporan($admin, $petugas);

        Livewire::test(ValidasiShowComponent::class, ['spk' => $spk])
            ->set("checked.{$rambuPasang->id}", false)
            ->call('lanjutkan')
            ->set('ubahDeadline', true)
            ->set('deadlineBaru', now()->subDay()->toDateString())
            ->set("catatanPenolakan.{$rambuPasang->id}", 'Perlu revisi.')
            ->call('konfirmasiPenolakan')
            ->assertHasErrors(['deadlineBaru']);

        $this->assertSame(0, AuditLog::where('aksi', 'deadline_diperpanjang')->count());
    }

    public function test_deadline_baru_of_today_is_rejected(): void
    {
        $admin = User::factory()->admin()->create();
        $petugas = User::factory()->create();
        $this->actingAs($admin);

        ['spk' => $spk, 'rambuPasang' => $rambuPasang] =
            $this->makeSpkWithPendingLaporan($admin, $petugas);

        Livewire::test(ValidasiShowComponent::class, ['spk' => $spk])
            ->set("checked.{$rambuPasang->id}", false)
            ->call('lanjutkan')
            ->set('ubahDeadline', true)
            ->set('deadlineBaru', now()->toDateString())
            ->set("catatanPenolakan.{$rambuPasang->id}", 'Perlu revisi.')
            ->call('konfirmasiPenolakan')
            ->assertHasErrors(['deadlineBaru' => 'after']);

        $this->assertSame(0, AuditLog::where('aksi', 'deadline_diperpanjang')->count());
    }

    // deadlineBaru is always pre-filled with the SPK's current deadline (see
    // lanjutkan()) even when the admin never checks "ubahDeadline" — for an
    // overdue-but-still-Aktif SPK (deadline already in the past, a normal
    // state this app allows), validating that pre-filled value unconditionally
    // would block rejecting a rambu entirely unless the admin also pushed the
    // deadline out, which isn't what leaving the checkbox unchecked means.
    public function test_can_reject_without_changing_deadline_even_when_spk_is_already_overdue(): void
    {
        $admin = User::factory()->admin()->create();
        $petugas = User::factory()->create();
        $this->actingAs($admin);

        ['spk' => $spk, 'rambuPasang' => $rambuPasang] =
            $this->makeSpkWithPendingLaporan($admin, $petugas);
        $spk->update(['deadline' => now()->subDays(3)]);

        Livewire::test(ValidasiShowComponent::class, ['spk' => $spk])
            ->set("checked.{$rambuPasang->id}", false)
            ->call('lanjutkan')
            ->set("catatanPenolakan.{$rambuPasang->id}", 'Perlu revisi.')
            ->call('konfirmasiPenolakan')
            ->assertHasNoErrors();

        $this->assertSame(StatusRambuPasang::Revisi, $rambuPasang->fresh()->status);
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

    public function test_validasi_includes_kendala_flagged_rambu_but_cannot_approve_it(): void
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
        $response->assertSee('Akan dikembalikan untuk direvisi');
        // No click-to-approve affordance rendered for the kendala card at all.
        $response->assertDontSee("wire:click=\"\$toggle('checked.{$rambuPasang->id}')\"", false);

        // A kendala report exists precisely because the work COULDN'T be
        // completed — there's no laporan_pengerjaan behind it to accept as
        // done. The UI never renders a way to check a kendala card (see
        // show.blade.php), but $checked is still a public Livewire property
        // reachable directly; this simulates that and confirms the server
        // ignores it rather than trusting the client.
        Livewire::test(ValidasiShowComponent::class, ['spk' => $spk])
            ->set("checked.{$rambuPasang->id}", true)
            ->call('lanjutkan')
            ->assertSet('showPenolakanForm', true)
            ->assertSet("checked.{$rambuPasang->id}", false);

        $this->assertSame(StatusRambuPasang::Tertunda, $rambuPasang->fresh()->status);
        $this->assertFalse($rambu->fresh()->sudah_terpasang);
        $this->assertSame(0, Notifikasi::where('user_id', $petugas->id)->count());
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

    public function test_validasi_show_displays_every_rambu_in_spk_not_just_pending(): void
    {
        $admin = User::factory()->admin()->create();
        $petugas = User::factory()->create();
        $this->actingAs($admin);

        ['spk' => $spk, 'rambu' => $rambuSelesai] = $this->makeSpkWithPendingLaporan($admin, $petugas);
        $rambuPasangSelesai = RambuPasang::where('rambu_spk_id', $spk->id)->first();
        $rambuPasangSelesai->update(['status' => 'selesai']);

        $jenisRambu = JenisRambu::create(['nama_jenis' => 'Rambu Petunjuk']);
        $rambuPending = Rambu::create([
            'jenis_rambu_id' => $jenisRambu->id,
            'wilayah' => 'Banjarmasin Tengah',
            'lokasi' => 'Simpang tiga baru',
            'koordinat' => '-3.31,114.59',
        ]);
        $rambuPasangPending = RambuPasang::create([
            'rambu_spk_id' => $spk->id,
            'rambu_id' => $rambuPending->id,
            'jenis_pekerjaan' => 'pasang_baru',
            'jumlah' => 1,
            'status' => 'menunggu_validasi',
        ]);
        LaporanPengerjaan::create([
            'rambu_pasang_id' => $rambuPasangPending->id,
            'dilaporkan_oleh' => $petugas->id,
            'status' => 'diajukan',
        ]);

        $response = $this->get(route('admin.validasi.show', $spk));

        $response->assertOk();
        $response->assertSee($rambuSelesai->lokasi);
        $response->assertSee('Simpang tiga baru');
    }
}
