<?php

namespace Tests\Feature\User;

use App\Enums\StatusLaporan;
use App\Enums\StatusRambuPasang;
use App\Livewire\User\Kendala as KendalaComponent;
use App\Livewire\User\Laporan as LaporanComponent;
use App\Livewire\User\Spk\Show as UserSpkShowComponent;
use App\Models\DikerjakanOleh;
use App\Models\JenisRambu;
use App\Models\Kendala;
use App\Models\LaporanPengerjaan;
use App\Models\Rambu;
use App\Models\RambuPasang;
use App\Models\Spk;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use Tests\TestCase;

class KendalaLaporanEditTest extends TestCase
{
    use RefreshDatabase;

    private function makeJoinedRambuPasang(User $admin, User $petugas, string $status = 'belum'): RambuPasang
    {
        $jenisRambu = JenisRambu::create(['nama_jenis' => 'Rambu Peringatan']);

        $rambu = Rambu::create([
            'jenis_rambu_id' => $jenisRambu->id,
            'wilayah' => 'Banjarmasin Tengah',
            'lokasi' => 'Perempatan dekat masjid',
            'koordinat' => '-3.3194,114.5908',
        ]);

        $spk = Spk::create([
            'nomor_surat' => 'SR-2026/BJM/0'.random_int(100, 999),
            'dibuat_oleh' => $admin->id,
            'wilayah' => 'Banjarmasin Tengah',
            'deadline' => now()->addDays(5),
            'urgensi' => 'sedang',
            'status' => 'aktif',
            'asal_permintaan' => 'internal',
        ]);

        DikerjakanOleh::create([
            'by_spk_id' => $spk->id,
            'by_user_id' => $petugas->id,
            'is_perwakilan' => true,
        ]);

        return RambuPasang::create([
            'rambu_spk_id' => $spk->id,
            'rambu_id' => $rambu->id,
            'jenis_pekerjaan' => 'pasang_baru',
            'jumlah' => 1,
            'status' => $status,
        ]);
    }

    public function test_petugas_can_edit_existing_kendala_in_place(): void
    {
        $admin = User::factory()->admin()->create();
        $petugas = User::factory()->create();
        $this->actingAs($petugas);

        $rp = $this->makeJoinedRambuPasang($admin, $petugas, 'tertunda');
        $kendala = Kendala::create([
            'rambu_pasang_id' => $rp->id,
            'dilaporkan_oleh' => $petugas->id,
            'alasan' => 'Alasan lama.',
            'foto' => 'kendala/lama.jpg',
        ]);

        Livewire::test(KendalaComponent::class)
            ->call('selectItem', $rp->id)
            ->assertSet('alasan', 'Alasan lama.')
            ->set('alasan', 'Alasan yang sudah diperbaiki.')
            ->call('submit')
            ->assertHasNoErrors();

        $this->assertSame(1, Kendala::count());
        $this->assertSame('Alasan yang sudah diperbaiki.', $kendala->fresh()->alasan);
        $this->assertSame('kendala/lama.jpg', $kendala->fresh()->foto);
        $this->assertSame(StatusRambuPasang::Tertunda, $rp->fresh()->status);
    }

    public function test_petugas_can_switch_from_laporan_to_kendala(): void
    {
        $admin = User::factory()->admin()->create();
        $petugas = User::factory()->create();
        $this->actingAs($petugas);

        $rp = $this->makeJoinedRambuPasang($admin, $petugas, 'menunggu_validasi');
        LaporanPengerjaan::create([
            'rambu_pasang_id' => $rp->id,
            'dilaporkan_oleh' => $petugas->id,
            'foto_sesudah' => 'laporan-pengerjaan/sesudah/lama.jpg',
            'status' => 'diajukan',
        ]);

        Livewire::test(KendalaComponent::class)
            ->call('selectItem', $rp->id)
            ->assertSet('alasan', '')
            ->set('alasan', 'Ternyata ada masalah baru.')
            ->set('foto', UploadedFile::fake()->image('kendala-baru.jpg'))
            ->call('submit')
            ->assertHasNoErrors();

        $this->assertSame(0, LaporanPengerjaan::count());
        $this->assertSame(1, Kendala::count());
        $this->assertSame(StatusRambuPasang::Tertunda, $rp->fresh()->status);
    }

    public function test_petugas_can_edit_existing_laporan_pengerjaan_in_place(): void
    {
        $admin = User::factory()->admin()->create();
        $petugas = User::factory()->create();
        $this->actingAs($petugas);

        $rp = $this->makeJoinedRambuPasang($admin, $petugas, 'menunggu_validasi');
        $laporan = LaporanPengerjaan::create([
            'rambu_pasang_id' => $rp->id,
            'dilaporkan_oleh' => $petugas->id,
            'foto_sesudah' => 'laporan-pengerjaan/sesudah/lama.jpg',
            'catatan_lapangan' => 'Catatan lama.',
            'status' => 'diajukan',
        ]);
        $laporan->barangBahan()->create(['nama' => 'Tiang lama', 'jumlah' => 1, 'satuan' => 'batang']);

        Livewire::test(LaporanComponent::class)
            ->call('selectItem', $rp->id)
            ->assertSet('catatan_lapangan', 'Catatan lama.')
            ->set('catatan_lapangan', 'Catatan yang sudah diperbarui.')
            ->set('barangBahan.0.nama', 'Tiang baru')
            ->set('barangBahan.0.jumlah', 5)
            ->set('barangBahan.0.satuan', 'batang')
            ->call('submit')
            ->assertHasNoErrors();

        $this->assertSame(1, LaporanPengerjaan::count());
        $laporan->refresh();
        $this->assertSame('Catatan yang sudah diperbarui.', $laporan->catatan_lapangan);
        $this->assertSame('laporan-pengerjaan/sesudah/lama.jpg', $laporan->foto_sesudah);
        $this->assertSame(1, $laporan->barangBahan()->count());
        $this->assertSame('Tiang baru', $laporan->barangBahan()->first()->nama);
        $this->assertSame(5, $laporan->barangBahan()->first()->jumlah);
        $this->assertSame(StatusRambuPasang::MenungguValidasi, $rp->fresh()->status);
    }

    public function test_petugas_can_switch_from_kendala_to_laporan(): void
    {
        $admin = User::factory()->admin()->create();
        $petugas = User::factory()->create();
        $this->actingAs($petugas);

        $rp = $this->makeJoinedRambuPasang($admin, $petugas, 'tertunda');
        Kendala::create([
            'rambu_pasang_id' => $rp->id,
            'dilaporkan_oleh' => $petugas->id,
            'alasan' => 'Kendala yang sudah teratasi.',
            'foto' => 'kendala/lama.jpg',
        ]);

        Livewire::test(LaporanComponent::class)
            ->call('selectItem', $rp->id)
            ->set('foto_sesudah', UploadedFile::fake()->image('sesudah.jpg'))
            ->call('submit')
            ->assertHasNoErrors();

        $this->assertSame(0, Kendala::count());
        $this->assertSame(1, LaporanPengerjaan::count());
        $this->assertSame(StatusRambuPasang::MenungguValidasi, $rp->fresh()->status);
    }

    public function test_spk_detail_shows_contextual_edit_labels_for_tertunda_and_menunggu_validasi(): void
    {
        $admin = User::factory()->admin()->create();
        $petugas = User::factory()->create();
        $this->actingAs($petugas);

        $rpTertunda = $this->makeJoinedRambuPasang($admin, $petugas, 'tertunda');
        Kendala::create([
            'rambu_pasang_id' => $rpTertunda->id,
            'dilaporkan_oleh' => $petugas->id,
            'alasan' => 'Alasan.',
            'foto' => 'kendala/lama.jpg',
        ]);

        Livewire::test(UserSpkShowComponent::class, ['spk' => $rpTertunda->spk])
            ->assertSee('Edit Kendala')
            ->assertSee('Tandai Selesai');

        $rpMenunggu = $this->makeJoinedRambuPasang($admin, $petugas, 'menunggu_validasi');
        LaporanPengerjaan::create([
            'rambu_pasang_id' => $rpMenunggu->id,
            'dilaporkan_oleh' => $petugas->id,
            'foto_sesudah' => 'laporan-pengerjaan/sesudah/lama.jpg',
            'status' => 'diajukan',
        ]);

        Livewire::test(UserSpkShowComponent::class, ['spk' => $rpMenunggu->spk])
            ->assertSee('Ada Kendala?')
            ->assertSee('Edit Laporan');
    }

    public function test_catatan_penolakan_shown_on_laporan_edit_form_when_revisi(): void
    {
        $admin = User::factory()->admin()->create();
        $petugas = User::factory()->create();
        $this->actingAs($petugas);

        $rp = $this->makeJoinedRambuPasang($admin, $petugas, 'revisi');
        LaporanPengerjaan::create([
            'rambu_pasang_id' => $rp->id,
            'dilaporkan_oleh' => $petugas->id,
            'foto_sesudah' => 'laporan-pengerjaan/sesudah/lama.jpg',
            'status' => StatusLaporan::Ditolak->value,
            'catatan_penolakan' => 'Foto kurang jelas, ulangi dari sudut lain.',
        ]);

        Livewire::test(LaporanComponent::class)
            ->call('selectItem', $rp->id)
            ->assertSee('Foto kurang jelas, ulangi dari sudut lain.');
    }

    public function test_catatan_penolakan_shown_on_kendala_edit_form_when_revisi(): void
    {
        $admin = User::factory()->admin()->create();
        $petugas = User::factory()->create();
        $this->actingAs($petugas);

        $rp = $this->makeJoinedRambuPasang($admin, $petugas, 'revisi');
        LaporanPengerjaan::create([
            'rambu_pasang_id' => $rp->id,
            'dilaporkan_oleh' => $petugas->id,
            'foto_sesudah' => 'laporan-pengerjaan/sesudah/lama.jpg',
            'status' => StatusLaporan::Ditolak->value,
            'catatan_penolakan' => 'Rambu belum terpasang dengan kuat.',
        ]);

        Livewire::test(KendalaComponent::class)
            ->call('selectItem', $rp->id)
            ->assertSee('Rambu belum terpasang dengan kuat.');
    }

    public function test_editing_locked_once_laporan_akhir_diajukan(): void
    {
        $admin = User::factory()->admin()->create();
        $petugas = User::factory()->create();
        $this->actingAs($petugas);

        $rp = $this->makeJoinedRambuPasang($admin, $petugas, 'tertunda');
        Kendala::create([
            'rambu_pasang_id' => $rp->id,
            'dilaporkan_oleh' => $petugas->id,
            'alasan' => 'Alasan.',
            'foto' => 'kendala/lama.jpg',
        ]);
        $rp->spk->update(['laporan_akhir_diajukan_at' => now()]);

        Livewire::test(KendalaComponent::class)
            ->call('selectItem', $rp->id)
            ->assertSee('Tidak ada tugas yang bisa diajukan kendala saat ini.');

        Livewire::test(LaporanComponent::class)
            ->call('selectItem', $rp->id)
            ->assertSee('Tidak ada tugas yang bisa dilaporkan saat ini.');
    }
}
