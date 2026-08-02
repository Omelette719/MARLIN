<?php

namespace Tests\Feature;

use App\Models\DikerjakanOleh;
use App\Models\JenisRambu;
use App\Models\Rambu;
use App\Models\RambuPasang;
use App\Models\RtPerwakilan;
use App\Models\Spk;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuratPengantarTest extends TestCase
{
    use RefreshDatabase;

    private function makeSpk(User $admin): Spk
    {
        $jenisRambu = JenisRambu::create(['nama_jenis' => 'Rambu Peringatan']);

        $rambu = Rambu::create([
            'jenis_rambu_id' => $jenisRambu->id,
            'wilayah' => 'Banjarmasin Tengah',
            'lokasi' => 'Perempatan dekat masjid',
            'koordinat' => '-3.3194,114.5908',
        ]);

        $spk = Spk::create([
            'nomor_surat' => 'SR-2026/BJM/0003',
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
            'status' => 'belum',
        ]);

        return $spk;
    }

    public function test_admin_can_download_surat_pengantar(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $spk = $this->makeSpk($admin);

        $response = $this->get(route('spk.surat-pengantar', $spk));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_joined_petugas_can_download_surat_pengantar(): void
    {
        $admin = User::factory()->admin()->create();
        $petugas = User::factory()->create();
        $this->actingAs($petugas);

        $spk = $this->makeSpk($admin);

        DikerjakanOleh::create([
            'by_spk_id' => $spk->id,
            'by_user_id' => $petugas->id,
            'is_perwakilan' => true,
        ]);

        $response = $this->get(route('spk.surat-pengantar', $spk));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_surat_pengantar_renders_with_tanggal_survei_and_rt_perwakilan(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $spk = $this->makeSpk($admin);
        $spk->update(['tanggal_survei' => '2026-06-15']);

        RtPerwakilan::create([
            'nama_lengkap' => 'RT. 27 Matoha',
            'no_telepon' => '08981112210',
            'rtperwakilan_spk_id' => $spk->id,
        ]);

        $response = $this->get(route('spk.surat-pengantar', $spk));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_perwakilan_column_removed_from_dikerjakan_oleh_table(): void
    {
        $admin = User::factory()->admin()->create();
        $spk = $this->makeSpk($admin);

        $petugas = User::factory()->create();
        DikerjakanOleh::create([
            'by_spk_id' => $spk->id,
            'by_user_id' => $petugas->id,
            'is_perwakilan' => true,
        ]);

        $spk->load(['rambuPasang.rambu.jenisRambu', 'dikerjakanOleh.user', 'pembuat', 'rtPerwakilan']);

        $html = view('pdf.surat-pengantar', ['spk' => $spk])->render();

        $this->assertStringNotContainsString('Perwakilan</th>', $html);
        $this->assertStringNotContainsString('>Ya<', $html);
    }

    public function test_mengetahui_block_never_shows_rt_perwakilan_name_even_when_present(): void
    {
        $admin = User::factory()->admin()->create();
        $spk = $this->makeSpk($admin);

        RtPerwakilan::create([
            'nama_lengkap' => 'Abdul',
            'no_telepon' => '08226735526',
            'rtperwakilan_spk_id' => $spk->id,
        ]);

        $spk->load(['rambuPasang.rambu.jenisRambu', 'dikerjakanOleh.user', 'pembuat', 'rtPerwakilan']);

        $html = view('pdf.surat-pengantar', ['spk' => $spk])->render();

        $this->assertStringContainsString('....................................................', $html);

        $mengetahuiPos = strpos($html, 'Mengetahui');
        $this->assertNotFalse($mengetahuiPos);
        $this->assertStringNotContainsString('Abdul', substr($html, $mengetahuiPos));

        // The contact person's name/phone still appear elsewhere in the letter (rt-box).
        $this->assertStringContainsString('Abdul', $html);
        $this->assertStringContainsString('08226735526', $html);
    }

    public function test_petugas_survei_never_shown_in_surat_pengantar_but_tanggal_survei_is(): void
    {
        $admin = User::factory()->admin()->create();
        $spk = $this->makeSpk($admin);
        $spk->update(['tanggal_survei' => '2026-06-15', 'petugas_survei' => 'Budi, Andi']);

        $spk->load(['rambuPasang.rambu.jenisRambu', 'dikerjakanOleh.user', 'pembuat', 'rtPerwakilan']);

        $html = view('pdf.surat-pengantar', ['spk' => $spk])->render();

        $this->assertStringContainsString('DISURVEI TGL 15 JUNI 2026', $html);
        $this->assertStringNotContainsString('Budi, Andi', $html);
    }

    public function test_unjoined_petugas_cannot_download_surat_pengantar(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs(User::factory()->create());

        $spk = $this->makeSpk($admin);

        $this->get(route('spk.surat-pengantar', $spk))->assertForbidden();
    }
}
