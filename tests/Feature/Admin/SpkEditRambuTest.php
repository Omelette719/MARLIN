<?php

namespace Tests\Feature\Admin;

use App\Enums\StatusRambuPasang;
use App\Livewire\Admin\Spk\Edit as SpkEditComponent;
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

class SpkEditRambuTest extends TestCase
{
    use RefreshDatabase;

    private function makeSpk(User $admin, string $jenisSpk = 'pasang_baru'): Spk
    {
        return Spk::create([
            'nomor_surat' => 'SR-2026/BJM/9'.random_int(100, 999),
            'dibuat_oleh' => $admin->id,
            'jenis_spk' => $jenisSpk,
            'jalan' => 'Veteran',
            'rt' => '5',
            'kelurahan' => 'Antasan Besar',
            'wilayah' => 'Jl. Veteran RT. 5 Kel. Antasan Besar',
            'deadline' => now()->addDays(10),
            'urgensi' => 'sedang',
            'status' => 'aktif',
            'asal_permintaan' => 'internal',
        ]);
    }

    private function makeRambuPasang(Spk $spk, string $jenisPekerjaan = 'pasang_baru'): RambuPasang
    {
        $jenis = JenisRambu::create(['nama_jenis' => 'Rambu Peringatan']);
        $rambu = Rambu::create([
            'jenis_rambu_id' => $jenis->id,
            'wilayah' => $spk->wilayah,
            'lokasi' => 'Depan kantor lurah',
            'koordinat' => '-3.30,114.59',
        ]);

        return RambuPasang::create([
            'rambu_spk_id' => $spk->id,
            'rambu_id' => $rambu->id,
            'jenis_pekerjaan' => $jenisPekerjaan,
            'jumlah' => 1,
            'status' => 'belum',
        ]);
    }

    public function test_admin_can_edit_existing_rambu_pasang_manual_fields_in_place(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $spk = $this->makeSpk($admin);
        $rp = $this->makeRambuPasang($spk);

        Livewire::test(SpkEditComponent::class, ['spk' => $spk])
            ->set('rambuItems.0.lokasi', 'Perempatan baru dekat sekolah')
            ->set('rambuItems.0.koordinat', '-3.31,114.60')
            ->set('rambuItems.0.jumlah', 3)
            ->call('save')
            ->assertHasNoErrors();

        $rp->refresh();
        $rp->rambu->refresh();

        $this->assertSame('Perempatan baru dekat sekolah', $rp->rambu->lokasi);
        $this->assertSame('-3.31,114.60', $rp->rambu->koordinat);
        $this->assertSame(3, $rp->jumlah);
        $this->assertSame(1, RambuPasang::count());
    }

    public function test_admin_can_swap_existing_rambu_pasang_to_a_different_registered_rambu(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $spk = $this->makeSpk($admin, 'perbaikan');
        $rp = $this->makeRambuPasang($spk, 'perbaikan');

        $jenis = JenisRambu::create(['nama_jenis' => 'Rambu Larangan']);
        $rambuLain = Rambu::create([
            'jenis_rambu_id' => $jenis->id,
            'wilayah' => 'Banjarmasin Utara',
            'lokasi' => 'Simpang tiga',
            'koordinat' => '-3.29,114.58',
            'sudah_terpasang' => true,
        ]);

        Livewire::test(SpkEditComponent::class, ['spk' => $spk])
            ->set('rambuItems.0.rambu_terdaftar', true)
            ->set('rambuItems.0.rambu_id', (string) $rambuLain->id)
            ->call('save')
            ->assertHasNoErrors();

        $rp->refresh();

        $this->assertSame($rambuLain->id, $rp->rambu_id);
    }

    public function test_admin_can_add_new_rambu_while_editing_spk(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $spk = $this->makeSpk($admin);
        $this->makeRambuPasang($spk);

        $jenis = JenisRambu::create(['nama_jenis' => 'Rambu Petunjuk']);

        Livewire::test(SpkEditComponent::class, ['spk' => $spk])
            ->call('addRambuItem')
            ->set('rambuItems.1.jenis_rambu_id', (string) $jenis->id)
            ->set('rambuItems.1.lokasi', 'Depan puskesmas')
            ->set('rambuItems.1.koordinat', '-3.32,114.61')
            ->set('rambuItems.1.jumlah', 1)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(2, RambuPasang::where('rambu_spk_id', $spk->id)->count());
    }

    // Same bug as Create.php: <flux:error name="rambuItems"> defaulted to
    // Flux's "deep" wildcard fallback, duplicating whatever nested
    // "rambuItems.*" field error already renders inline in that item's own
    // card, with Laravel's raw un-humanized attribute path instead of a
    // clean label.
    public function test_incomplete_new_rambu_item_shows_a_clean_error_exactly_once(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $spk = $this->makeSpk($admin);
        $this->makeRambuPasang($spk);

        $response = Livewire::test(SpkEditComponent::class, ['spk' => $spk])
            ->call('addRambuItem')
            ->set('rambuItems.1.lokasi', 'Depan puskesmas')
            ->set('rambuItems.1.koordinat', '-3.32,114.61')
            ->set('rambuItems.1.jumlah', 1)
            ->call('save')
            ->assertHasErrors(['rambuItems.1.jenis_rambu_id']);

        $response->assertDontSee('rambu items.1.jenis rambu id', false);
        $this->assertSame(1, substr_count($response->html(), 'The Jenis Rambu field is required.'));
    }

    public function test_admin_can_batalkan_single_rambu_with_reason(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $spk = $this->makeSpk($admin);
        $rp = $this->makeRambuPasang($spk);

        $petugas = User::factory()->create();
        DikerjakanOleh::create([
            'by_spk_id' => $spk->id,
            'by_user_id' => $petugas->id,
            'is_perwakilan' => true,
        ]);

        Livewire::test(SpkEditComponent::class, ['spk' => $spk])
            ->call('bukaBatalkanRambu', 0)
            ->set('catatan_pembatalan', 'Salah lokasi, sudah ada rambu lain di sana.')
            ->call('konfirmasiBatalkanRambu')
            ->assertHasNoErrors();

        $rp->refresh();

        $this->assertSame(StatusRambuPasang::Batal, $rp->status);
        $this->assertSame('Salah lokasi, sudah ada rambu lain di sana.', $rp->catatan_pembatalan);
        $this->assertSame(1, $spk->auditLogs()->where('aksi', 'rambu_pasang_dibatalkan')->count());
        $this->assertSame('aktif', $spk->fresh()->status->value);
        $this->assertSame(1, Notifikasi::where('user_id', $petugas->id)->where('judul', 'Rambu Dibatalkan')->count());
        $this->assertSame(
            route('user.spk.show', $spk),
            Notifikasi::where('user_id', $petugas->id)->where('judul', 'Rambu Dibatalkan')->first()->url
        );
    }

    public function test_batalkan_single_rambu_requires_reason(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $spk = $this->makeSpk($admin);
        $rp = $this->makeRambuPasang($spk);

        Livewire::test(SpkEditComponent::class, ['spk' => $spk])
            ->call('bukaBatalkanRambu', 0)
            ->set('catatan_pembatalan', '')
            ->call('konfirmasiBatalkanRambu')
            ->assertHasErrors(['catatan_pembatalan' => 'required']);

        $this->assertSame(StatusRambuPasang::Belum, $rp->fresh()->status);
    }

    // See the equivalent Temuan Lapangan test for why this matters: a
    // per-item confirm modal without wire:key can end up bound to the
    // wrong row once the list re-renders after a delete.
    public function test_hapus_rambu_modal_is_keyed_per_rambu(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $spk = $this->makeSpk($admin);
        $rp = $this->makeRambuPasang($spk);

        Livewire::test(SpkEditComponent::class, ['spk' => $spk])
            ->assertSeeHtml('wire:key="hapus-rambu-modal-'.$rp->id.'"');
    }

    public function test_edit_shows_visible_error_when_last_rambu_is_removed(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $spk = $this->makeSpk($admin);
        $this->makeRambuPasang($spk);

        Livewire::test(SpkEditComponent::class, ['spk' => $spk])
            ->call('hapusRambu', 0)
            ->call('save')
            ->assertHasErrors(['rambuItems'])
            // Same <flux:error> slot-content bug as Create's version of this
            // check — the message never rendered even though the validation
            // itself correctly blocked the save.
            ->assertSee('Minimal harus ada satu rambu.');
    }

    public function test_admin_can_hapus_rambu_pasang_with_no_progress(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $spk = $this->makeSpk($admin);
        $rp = $this->makeRambuPasang($spk);
        // Add a second rambu so the SPK isn't left with zero after deletion.
        $this->makeRambuPasang($spk);

        Livewire::test(SpkEditComponent::class, ['spk' => $spk])
            ->call('hapusRambu', 0);

        $this->assertNull(RambuPasang::find($rp->id));
        $this->assertSame(1, RambuPasang::where('rambu_spk_id', $spk->id)->count());
    }

    public function test_admin_cannot_hapus_rambu_pasang_with_existing_kendala(): void
    {
        $admin = User::factory()->admin()->create();
        $petugas = User::factory()->create();
        $this->actingAs($admin);

        $spk = $this->makeSpk($admin);
        $rp = $this->makeRambuPasang($spk);
        $rp->update(['status' => 'tertunda']);

        Kendala::create([
            'rambu_pasang_id' => $rp->id,
            'dilaporkan_oleh' => $petugas->id,
            'alasan' => 'Warga menolak.',
            'foto' => 'kendala/fake.jpg',
        ]);

        Livewire::test(SpkEditComponent::class, ['spk' => $spk])
            ->call('hapusRambu', 0);

        $this->assertNotNull(RambuPasang::find($rp->id));
    }

    public function test_admin_cannot_hapus_rambu_pasang_with_existing_laporan_pengerjaan(): void
    {
        $admin = User::factory()->admin()->create();
        $petugas = User::factory()->create();
        $this->actingAs($admin);

        $spk = $this->makeSpk($admin);
        $rp = $this->makeRambuPasang($spk);
        $rp->update(['status' => 'menunggu_validasi']);

        LaporanPengerjaan::create([
            'rambu_pasang_id' => $rp->id,
            'dilaporkan_oleh' => $petugas->id,
            'foto_sesudah' => 'laporan-pengerjaan/fake.jpg',
            'status' => 'diajukan',
        ]);

        Livewire::test(SpkEditComponent::class, ['spk' => $spk])
            ->call('hapusRambu', 0);

        $this->assertNotNull(RambuPasang::find($rp->id));
    }

    public function test_batalkan_single_rambu_does_not_affect_other_rambu_or_spk_status(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $spk = $this->makeSpk($admin);
        $rp1 = $this->makeRambuPasang($spk);
        $rp2 = $this->makeRambuPasang($spk);

        Livewire::test(SpkEditComponent::class, ['spk' => $spk])
            ->call('bukaBatalkanRambu', 0)
            ->set('catatan_pembatalan', 'Rambu tidak jadi dipasang di sini.')
            ->call('konfirmasiBatalkanRambu');

        $this->assertSame(StatusRambuPasang::Batal, $rp1->fresh()->status);
        $this->assertSame(StatusRambuPasang::Belum, $rp2->fresh()->status);
        $this->assertSame('aktif', $spk->fresh()->status->value);
    }

    public function test_invalid_asal_permintaan_value_is_rejected_on_edit(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $spk = $this->makeSpk($admin);
        $this->makeRambuPasang($spk);

        Livewire::test(SpkEditComponent::class, ['spk' => $spk])
            ->set('asal_permintaan', 'bukan_asal_yang_valid')
            ->call('save')
            ->assertHasErrors(['asal_permintaan']);

        $this->assertSame('internal', $spk->fresh()->asal_permintaan->value);
    }

    public function test_koordinat_with_unparsable_format_is_rejected_on_edit(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $spk = $this->makeSpk($admin);
        $rp = $this->makeRambuPasang($spk);

        Livewire::test(SpkEditComponent::class, ['spk' => $spk])
            ->set('rambuItems.0.koordinat', 'entah dimana')
            ->call('save')
            ->assertHasErrors(['rambuItems.0.koordinat']);

        $this->assertSame('-3.30,114.59', $rp->rambu->fresh()->koordinat);
    }

    public function test_cannot_swap_a_rambu_to_one_already_used_by_another_item_in_the_same_spk(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $spk = $this->makeSpk($admin, 'perbaikan');
        $rp1 = $this->makeRambuPasang($spk, 'perbaikan');
        $rp2 = $this->makeRambuPasang($spk, 'perbaikan');

        Livewire::test(SpkEditComponent::class, ['spk' => $spk])
            ->set('rambuItems.1.rambu_terdaftar', true)
            ->set('rambuItems.1.rambu_id', (string) $rp1->rambu_id)
            ->call('save')
            ->assertHasErrors(['rambuItems']);

        $this->assertSame($rp2->rambu_id, $rp2->fresh()->rambu_id);
    }

    public function test_editing_koordinat_in_place_does_not_warn_about_its_own_rambu(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $spk = $this->makeSpk($admin);
        $this->makeRambuPasang($spk);

        $component = Livewire::test(SpkEditComponent::class, ['spk' => $spk])
            ->set('rambuItems.0.koordinat', '-3.30,114.59');

        $this->assertEmpty($component->get('koordinatWarnings')[0] ?? null);
    }

    public function test_live_koordinat_input_warns_about_a_different_nearby_rambu_on_edit(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $spk = $this->makeSpk($admin);
        $this->makeRambuPasang($spk);

        $jenisLain = JenisRambu::create(['nama_jenis' => 'Rambu Larangan']);
        Rambu::create([
            'jenis_rambu_id' => $jenisLain->id,
            'wilayah' => 'Banjarmasin Utara',
            'lokasi' => 'Simpang tiga dekat sana',
            'koordinat' => '-3.29,114.58',
            'sudah_terpasang' => true,
        ]);

        $component = Livewire::test(SpkEditComponent::class, ['spk' => $spk])
            ->set('rambuItems.0.koordinat', '-3.29,114.58');

        $warnings = $component->get('koordinatWarnings');

        $this->assertNotEmpty($warnings[0] ?? null);
        $this->assertStringContainsString('Simpang tiga dekat sana', $warnings[0][0]['label']);
    }
}
