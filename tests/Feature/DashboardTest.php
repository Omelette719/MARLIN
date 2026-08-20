<?php

namespace Tests\Feature;

use App\Livewire\User\Dashboard as UserDashboardComponent;
use App\Models\DikerjakanOleh;
use App\Models\JenisRambu;
use App\Models\Rambu;
use App\Models\RambuPasang;
use App\Models\Spk;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private function makeAktifSpk(User $admin, array $attrs = []): Spk
    {
        static $urut = 0;

        return Spk::create(array_merge([
            'nomor_surat' => 'SR-2026/BJM/9'.str_pad((string) ++$urut, 3, '0', STR_PAD_LEFT),
            'dibuat_oleh' => $admin->id,
            'wilayah' => 'Banjarmasin Tengah',
            'deadline' => now()->addDays(10),
            'urgensi' => 'rendah',
            'status' => 'aktif',
            'asal_permintaan' => 'internal',
        ], $attrs));
    }

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_visit_the_dashboard(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('dashboard'));
        $response->assertOk();
    }

    // The dashboard lists ALL active SPKs (not just joined ones — see
    // ALUR-BISNIS.md), so the download button has to be blocked client-side
    // for SPKs the petugas hasn't joined instead of linking straight to a
    // route that would 403 them.
    public function test_petugas_gets_a_toast_instead_of_a_dead_link_for_an_unjoined_spks_download_button(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs(User::factory()->create());

        $jenisRambu = JenisRambu::create(['nama_jenis' => 'Rambu Peringatan']);
        $rambu = Rambu::create([
            'jenis_rambu_id' => $jenisRambu->id,
            'wilayah' => 'Banjarmasin Tengah',
            'lokasi' => 'Perempatan dekat masjid',
            'koordinat' => '-3.3194,114.5908',
        ]);
        $spk = Spk::create([
            'nomor_surat' => 'SR-2026/BJM/0009',
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

        Livewire::test(UserDashboardComponent::class)
            ->assertDontSeeHtml('href="'.route('spk.surat-pengantar', $spk).'"')
            ->call('tautanSuratPengantarDitolak')
            ->assertDispatched('toast-show');
    }

    // Stat cards summarize every active SPK shown in the "papan pekerjaan"
    // list below, not just the ones this petugas has personally joined —
    // otherwise they'd read as wrong (e.g. showing 0) even while the list
    // right underneath is clearly showing active surat.
    public function test_aktif_count_includes_all_active_spk_not_just_joined(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs(User::factory()->create());

        $this->makeAktifSpk($admin);
        $this->makeAktifSpk($admin);
        $this->makeAktifSpk($admin, ['status' => 'selesai']);

        $aktifCount = Livewire::test(UserDashboardComponent::class)->viewData('aktifCount');

        $this->assertSame(2, $aktifCount);
    }

    public function test_mendekati_deadline_count_includes_unjoined_spk_with_tinggi_urgensi(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs(User::factory()->create());

        $this->makeAktifSpk($admin, ['deadline' => now()->addDays(2)]);
        $this->makeAktifSpk($admin, ['deadline' => now()->addDays(10)]);

        $mendekatiDeadlineCount = Livewire::test(UserDashboardComponent::class)->viewData('mendekatiDeadlineCount');

        $this->assertSame(1, $mendekatiDeadlineCount);
    }

    public function test_progres_count_counts_spk_with_any_team_joined_not_just_own(): void
    {
        $admin = User::factory()->admin()->create();
        $lain = User::factory()->create();
        $this->actingAs(User::factory()->create());

        $sudahAdaTim = $this->makeAktifSpk($admin);
        DikerjakanOleh::create(['by_spk_id' => $sudahAdaTim->id, 'by_user_id' => $lain->id, 'is_perwakilan' => true]);

        $this->makeAktifSpk($admin);

        $progresCount = Livewire::test(UserDashboardComponent::class)->viewData('progresCount');

        $this->assertSame(1, $progresCount);
    }

    private function makeRambuPasangFor(Spk $spk, string $kelurahan = 'Antasan Besar', string $koordinat = '-3.3194,114.5908'): RambuPasang
    {
        static $urut = 0;

        $jenisRambu = JenisRambu::firstOrCreate(['nama_jenis' => 'Rambu Peringatan']);
        $rambu = Rambu::create([
            'jenis_rambu_id' => $jenisRambu->id,
            'kelurahan' => $kelurahan,
            'lokasi' => 'Titik '.(++$urut),
            'koordinat' => $koordinat,
            'sudah_terpasang' => false,
        ]);

        return RambuPasang::create([
            'rambu_spk_id' => $spk->id,
            'rambu_id' => $rambu->id,
            'jenis_pekerjaan' => 'pasang_baru',
            'jumlah' => 1,
            'status' => 'belum',
        ]);
    }

    public function test_spk_perlu_perhatian_only_includes_joined_spk(): void
    {
        $admin = User::factory()->admin()->create();
        $petugas = User::factory()->create();
        $this->actingAs($petugas);

        $diikuti = $this->makeAktifSpk($admin);
        $this->makeRambuPasangFor($diikuti);
        DikerjakanOleh::create(['by_spk_id' => $diikuti->id, 'by_user_id' => $petugas->id, 'is_perwakilan' => true]);

        $tidakDiikuti = $this->makeAktifSpk($admin);
        $this->makeRambuPasangFor($tidakDiikuti);

        $spkPerluPerhatian = Livewire::test(UserDashboardComponent::class)->viewData('spkPerluPerhatian');

        $this->assertCount(1, $spkPerluPerhatian);
        $this->assertSame($diikuti->id, $spkPerluPerhatian->first()['spk']->id);
    }

    public function test_saran_spk_excludes_already_claimed_and_already_joined_spk(): void
    {
        $admin = User::factory()->admin()->create();
        $lain = User::factory()->create();
        $petugas = User::factory()->create();
        $this->actingAs($petugas);

        $sudahDiikuti = $this->makeAktifSpk($admin);
        $this->makeRambuPasangFor($sudahDiikuti);
        DikerjakanOleh::create(['by_spk_id' => $sudahDiikuti->id, 'by_user_id' => $petugas->id, 'is_perwakilan' => true]);

        $sudahAdaTimLain = $this->makeAktifSpk($admin);
        $this->makeRambuPasangFor($sudahAdaTimLain);
        DikerjakanOleh::create(['by_spk_id' => $sudahAdaTimLain->id, 'by_user_id' => $lain->id, 'is_perwakilan' => true]);

        $belumAdaTim = $this->makeAktifSpk($admin);
        $this->makeRambuPasangFor($belumAdaTim);

        $saranSpk = Livewire::test(UserDashboardComponent::class)->viewData('saranSpk');

        $this->assertCount(1, $saranSpk);
        $this->assertSame($belumAdaTim->id, $saranSpk->first()['spk']->id);
    }

    public function test_saran_spk_prioritizes_same_kecamatan_as_petugas_own_active_work(): void
    {
        $admin = User::factory()->admin()->create();
        $petugas = User::factory()->create();
        $this->actingAs($petugas);

        // Pekerjaan aktif petugas ini ada di Pengambangan (Kec. Banjarmasin Timur).
        $sedangDikerjakan = $this->makeAktifSpk($admin);
        $this->makeRambuPasangFor($sedangDikerjakan, kelurahan: 'Pengambangan', koordinat: '-3.30,114.60');
        DikerjakanOleh::create(['by_spk_id' => $sedangDikerjakan->id, 'by_user_id' => $petugas->id, 'is_perwakilan' => true]);

        // Kebun Bunga juga di Kec. Banjarmasin Timur — kecamatan sama.
        $kecamatanSama = $this->makeAktifSpk($admin, ['deadline' => now()->addDays(20)]);
        $this->makeRambuPasangFor($kecamatanSama, kelurahan: 'Kebun Bunga', koordinat: '-3.31,114.61');

        // Telawang di Kec. Banjarmasin Barat — kecamatan lain, jauh lebih dekat
        // deadline-nya (kalau sort jatuh ke deadline, ini akan menang duluan).
        $kecamatanLain = $this->makeAktifSpk($admin, ['deadline' => now()->addDay()]);
        $this->makeRambuPasangFor($kecamatanLain, kelurahan: 'Telawang', koordinat: '-3.32,114.57');

        $saranSpk = Livewire::test(UserDashboardComponent::class)->viewData('saranSpk');

        $this->assertSame($kecamatanSama->id, $saranSpk->first()['spk']->id);
        $this->assertTrue($saranSpk->first()['samaKecamatan']);
    }

    public function test_saran_spk_falls_back_to_deadline_order_without_a_reference_point(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs(User::factory()->create());

        $lambat = $this->makeAktifSpk($admin, ['deadline' => now()->addDays(20)]);
        $this->makeRambuPasangFor($lambat);

        $cepat = $this->makeAktifSpk($admin, ['deadline' => now()->addDays(2)]);
        $this->makeRambuPasangFor($cepat);

        $saranSpk = Livewire::test(UserDashboardComponent::class)->viewData('saranSpk');

        $this->assertSame($cepat->id, $saranSpk->first()['spk']->id);
    }
}
