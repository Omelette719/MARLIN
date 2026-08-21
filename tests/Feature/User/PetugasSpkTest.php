<?php

namespace Tests\Feature\User;

use App\Enums\StatusLaporan;
use App\Enums\StatusRambuPasang;
use App\Livewire\Admin\Validasi\Show as ValidasiShowComponent;
use App\Livewire\User\Dashboard as UserDashboardComponent;
use App\Livewire\User\Kendala as KendalaComponent;
use App\Livewire\User\Laporan as LaporanComponent;
use App\Livewire\User\Spk\Show as UserSpkShowComponent;
use App\Models\AuditLog;
use App\Models\ContactPerson;
use App\Models\DikerjakanOleh;
use App\Models\JenisRambu;
use App\Models\Kendala;
use App\Models\LaporanPengerjaan;
use App\Models\Notifikasi;
use App\Models\Rambu;
use App\Models\RambuPasang;
use App\Models\Spk;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use Tests\TestCase;

class PetugasSpkTest extends TestCase
{
    use RefreshDatabase;

    private function makeRambuPasang(User $admin, string $status = 'belum'): RambuPasang
    {
        $jenisRambu = JenisRambu::create(['nama_jenis' => 'Rambu Peringatan']);

        $rambu = Rambu::create([
            'jenis_rambu_id' => $jenisRambu->id,
            'wilayah' => 'Banjarmasin Tengah',
            'lokasi' => 'Perempatan dekat masjid',
            'koordinat' => '-3.3194,114.5908',
        ]);

        $spk = Spk::create([
            'nomor_surat' => 'SR-2026/BJM/0001',
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

    public function test_petugas_can_view_daftar_surat_aktif(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(route('dashboard'))->assertOk();
    }

    public function test_daftar_surat_aktif_shows_progress_status_badge(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs(User::factory()->create());

        $rambuPasang = $this->makeRambuPasang($admin, status: 'belum');

        $response = $this->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee($rambuPasang->spk->nomor_surat);
        $response->assertSee('Belum');
    }

    private function makeSpkWithNomor(User $admin, string $nomorSurat): Spk
    {
        return Spk::create([
            'nomor_surat' => $nomorSurat,
            'dibuat_oleh' => $admin->id,
            'wilayah' => 'Banjarmasin Tengah',
            'deadline' => now()->addDays(5),
            'urgensi' => 'sedang',
            'status' => 'aktif',
            'asal_permintaan' => 'internal',
        ]);
    }

    public function test_daftar_surat_aktif_distinguishes_no_team_joined_by_me_and_joined_by_others(): void
    {
        $admin = User::factory()->admin()->create();
        $me = User::factory()->create();
        $this->actingAs($me);

        // Tanpa tim sama sekali.
        $this->makeRambuPasang($admin, status: 'belum');

        // Saya sudah gabung.
        $spkSayaGabung = $this->makeSpkWithNomor($admin, 'SR-2026/BJM/9101');
        DikerjakanOleh::create([
            'by_spk_id' => $spkSayaGabung->id,
            'by_user_id' => $me->id,
            'is_perwakilan' => true,
        ]);

        // Sudah ada tim, tapi bukan saya.
        $spkTimLain = $this->makeSpkWithNomor($admin, 'SR-2026/BJM/9102');
        DikerjakanOleh::create([
            'by_spk_id' => $spkTimLain->id,
            'by_user_id' => User::factory()->create()->id,
            'is_perwakilan' => true,
        ]);

        $response = $this->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Belum Ada Tim');
        $response->assertSee('Sudah Bergabung');
        $response->assertSee('Sudah Ada Tim Lain');
    }

    public function test_daftar_surat_aktif_progress_status_prefers_most_urgent_of_multiple_rambu(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs(User::factory()->create());

        $rambuPasang = $this->makeRambuPasang($admin, status: 'belum');

        $jenisRambu = JenisRambu::create(['nama_jenis' => 'Rambu Larangan']);
        $rambuKedua = Rambu::create([
            'jenis_rambu_id' => $jenisRambu->id,
            'wilayah' => 'Banjarmasin Tengah',
            'lokasi' => 'Simpang tiga',
            'koordinat' => '-3.30,114.60',
        ]);

        RambuPasang::create([
            'rambu_spk_id' => $rambuPasang->rambu_spk_id,
            'rambu_id' => $rambuKedua->id,
            'jenis_pekerjaan' => 'pasang_baru',
            'jumlah' => 1,
            'status' => 'tertunda',
        ]);

        $response = $this->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Tertunda');
    }

    public function test_daftar_surat_aktif_progress_status_does_not_show_menunggu_validasi_before_laporan_akhir_diajukan(): void
    {
        $admin = User::factory()->admin()->create();
        $petugas = User::factory()->create();
        $this->actingAs($petugas);

        // Satu-satunya rambu sudah dilaporkan, tapi laporan akhir SPK belum
        // diajukan — badge belum boleh bilang "Menunggu Validasi".
        $rambuPasang = $this->makeRambuPasang($admin, status: 'menunggu_validasi');

        DikerjakanOleh::create([
            'by_spk_id' => $rambuPasang->rambu_spk_id,
            'by_user_id' => $petugas->id,
            'is_perwakilan' => true,
        ]);

        // Scoped to the SPK card's own badge via viewData rather than a
        // page-wide assertDontSee('Menunggu Validasi') — the dashboard's peta
        // widget now also renders that exact string in its Tingkat filter
        // dropdown and color legend, which are unrelated to this badge.
        $item = Livewire::test(UserDashboardComponent::class)->viewData('spk')->first();

        $this->assertNotSame(StatusRambuPasang::MenungguValidasi, $item->progress_status);

        $response = $this->get(route('dashboard'));
        $response->assertOk();
        $response->assertSee('Siap Diajukan Laporan Akhir');
    }

    public function test_daftar_surat_aktif_progress_status_shows_menunggu_validasi_once_laporan_akhir_diajukan(): void
    {
        $admin = User::factory()->admin()->create();
        $petugas = User::factory()->create();
        $this->actingAs($petugas);

        $rambuPasang = $this->makeRambuPasang($admin, status: 'menunggu_validasi');
        $spk = $rambuPasang->spk;
        $spk->update(['laporan_akhir_diajukan_at' => now()]);

        DikerjakanOleh::create([
            'by_spk_id' => $spk->id,
            'by_user_id' => $petugas->id,
            'is_perwakilan' => true,
        ]);

        $response = $this->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Menunggu Validasi');
    }

    public function test_admin_is_redirected_away_from_petugas_dashboard(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $this->get(route('dashboard'))->assertRedirect(route('admin.dashboard'));
    }

    public function test_perwakilan_can_register_team_with_members(): void
    {
        $admin = User::factory()->admin()->create();
        $petugas = User::factory()->create();
        $anggota = User::factory()->create();
        $this->actingAs($petugas);

        $rambuPasang = $this->makeRambuPasang($admin);
        $spk = $rambuPasang->spk;

        Livewire::test(UserSpkShowComponent::class, ['spk' => $spk])
            ->assertSee($spk->nomor_surat)
            ->set('anggotaIds', [(string) $anggota->id])
            ->call('daftarkanTim');

        $this->assertDatabaseHas('dikerjakan_oleh', [
            'by_spk_id' => $spk->id,
            'by_user_id' => $petugas->id,
            'is_perwakilan' => true,
        ]);
        $this->assertDatabaseHas('dikerjakan_oleh', [
            'by_spk_id' => $spk->id,
            'by_user_id' => $anggota->id,
            'is_perwakilan' => false,
        ]);
    }

    public function test_petugas_cannot_register_team_if_one_already_exists(): void
    {
        $admin = User::factory()->admin()->create();
        $rambuPasang = $this->makeRambuPasang($admin);
        $spk = $rambuPasang->spk;

        DikerjakanOleh::create([
            'by_spk_id' => $spk->id,
            'by_user_id' => User::factory()->create()->id,
            'is_perwakilan' => true,
        ]);

        $petugasKedua = User::factory()->create();
        $this->actingAs($petugasKedua);

        // Was a silent no-op: the button that reaches this only renders
        // while $tim is empty, but two petugas can both load the page
        // while it's still unclaimed and both submit — whoever loses that
        // race used to get nothing at all telling them what happened.
        Livewire::test(UserSpkShowComponent::class, ['spk' => $spk])
            ->call('daftarkanTim')
            ->assertDispatched('toast-show', fn ($name, $params) => $params['dataset']['variant'] === 'warning')
            ->assertRedirect(route('user.spk.show', $spk));

        $this->assertDatabaseMissing('dikerjakan_oleh', [
            'by_spk_id' => $spk->id,
            'by_user_id' => $petugasKedua->id,
        ]);
    }

    public function test_perwakilan_can_add_more_members_later(): void
    {
        $admin = User::factory()->admin()->create();
        $petugas = User::factory()->create();
        $anggotaBaru = User::factory()->create();
        $this->actingAs($petugas);

        $rambuPasang = $this->makeRambuPasang($admin);
        $spk = $rambuPasang->spk;

        DikerjakanOleh::create([
            'by_spk_id' => $spk->id,
            'by_user_id' => $petugas->id,
            'is_perwakilan' => true,
        ]);

        Livewire::test(UserSpkShowComponent::class, ['spk' => $spk])
            ->set('anggotaIds', [(string) $anggotaBaru->id])
            ->call('tambahAnggota');

        $this->assertDatabaseHas('dikerjakan_oleh', [
            'by_spk_id' => $spk->id,
            'by_user_id' => $anggotaBaru->id,
            'is_perwakilan' => false,
        ]);
    }

    public function test_tambah_anggota_requires_at_least_one_selection(): void
    {
        $admin = User::factory()->admin()->create();
        $petugas = User::factory()->create();
        $this->actingAs($petugas);

        $rambuPasang = $this->makeRambuPasang($admin);
        $spk = $rambuPasang->spk;

        DikerjakanOleh::create([
            'by_spk_id' => $spk->id,
            'by_user_id' => $petugas->id,
            'is_perwakilan' => true,
        ]);

        // Asserted as a dispatched toast, not just a validation error: the
        // multiselect field this error would normally render under sits
        // behind this action's own confirm modal, so a toast is the only
        // feedback actually visible at the moment the click happens.
        Livewire::test(UserSpkShowComponent::class, ['spk' => $spk])
            ->set('anggotaIds', [])
            ->call('tambahAnggota')
            ->assertHasErrors(['anggotaIds'])
            ->assertSee('Pilih minimal satu anggota untuk ditambahkan.')
            ->assertDispatched('toast-show', fn ($name, $params) => $params['dataset']['variant'] === 'danger');

        $this->assertSame(1, DikerjakanOleh::where('by_spk_id', $spk->id)->count());
    }

    public function test_tambah_anggota_does_not_claim_success_when_everyone_selected_is_already_on_the_team(): void
    {
        $admin = User::factory()->admin()->create();
        $petugas = User::factory()->create();
        $sudahAnggota = User::factory()->create();
        $this->actingAs($petugas);

        $rambuPasang = $this->makeRambuPasang($admin);
        $spk = $rambuPasang->spk;

        DikerjakanOleh::create([
            'by_spk_id' => $spk->id,
            'by_user_id' => $petugas->id,
            'is_perwakilan' => true,
        ]);
        DikerjakanOleh::create([
            'by_spk_id' => $spk->id,
            'by_user_id' => $sudahAnggota->id,
            'is_perwakilan' => false,
        ]);

        // Simulates a stale dropdown (e.g. loaded before someone else added
        // this same person) rather than going through the UI, which would
        // normally already exclude existing members from the options list.
        Livewire::test(UserSpkShowComponent::class, ['spk' => $spk])
            ->set('anggotaIds', [(string) $sudahAnggota->id])
            ->call('tambahAnggota')
            ->assertHasNoErrors()
            ->assertDispatched('toast-show', fn ($name, $params) => $params['dataset']['variant'] === 'warning');

        $this->assertSame(2, DikerjakanOleh::where('by_spk_id', $spk->id)->count());
    }

    public function test_non_perwakilan_cannot_add_members(): void
    {
        $admin = User::factory()->admin()->create();
        $petugas = User::factory()->create();
        $anggotaBaru = User::factory()->create();
        $this->actingAs($petugas);

        $rambuPasang = $this->makeRambuPasang($admin);
        $spk = $rambuPasang->spk;

        DikerjakanOleh::create([
            'by_spk_id' => $spk->id,
            'by_user_id' => User::factory()->create()->id,
            'is_perwakilan' => true,
        ]);
        DikerjakanOleh::create([
            'by_spk_id' => $spk->id,
            'by_user_id' => $petugas->id,
            'is_perwakilan' => false,
        ]);

        Livewire::test(UserSpkShowComponent::class, ['spk' => $spk])
            ->set('anggotaIds', [(string) $anggotaBaru->id])
            ->call('tambahAnggota');

        $this->assertDatabaseMissing('dikerjakan_oleh', [
            'by_spk_id' => $spk->id,
            'by_user_id' => $anggotaBaru->id,
        ]);
    }

    // Same DOM-identity concern as the Temuan Lapangan/hapus-rambu modals:
    // without a stable wire:key, the modal for one member could end up
    // showing (or refusing to show at all) for a different one after the
    // team list re-renders following a removal.
    public function test_hapus_anggota_modal_is_keyed_per_member(): void
    {
        $admin = User::factory()->admin()->create();
        $petugas = User::factory()->create();
        $anggota = User::factory()->create();
        $this->actingAs($petugas);

        $rambuPasang = $this->makeRambuPasang($admin);
        $spk = $rambuPasang->spk;

        DikerjakanOleh::create(['by_spk_id' => $spk->id, 'by_user_id' => $petugas->id, 'is_perwakilan' => true]);
        $anggotaRow = DikerjakanOleh::create(['by_spk_id' => $spk->id, 'by_user_id' => $anggota->id, 'is_perwakilan' => false]);

        Livewire::test(UserSpkShowComponent::class, ['spk' => $spk])
            ->assertSeeHtml('wire:key="hapus-anggota-modal-'.$anggotaRow->id.'"');
    }

    public function test_perwakilan_can_remove_a_non_perwakilan_member(): void
    {
        $admin = User::factory()->admin()->create();
        $petugas = User::factory()->create();
        $anggota = User::factory()->create(['name' => 'Anggota Salah Input']);
        $this->actingAs($petugas);

        $rambuPasang = $this->makeRambuPasang($admin);
        $spk = $rambuPasang->spk;

        DikerjakanOleh::create([
            'by_spk_id' => $spk->id,
            'by_user_id' => $petugas->id,
            'is_perwakilan' => true,
        ]);
        $anggotaRow = DikerjakanOleh::create([
            'by_spk_id' => $spk->id,
            'by_user_id' => $anggota->id,
            'is_perwakilan' => false,
        ]);

        Livewire::test(UserSpkShowComponent::class, ['spk' => $spk])
            ->call('hapusAnggota', $anggotaRow->id);

        $this->assertDatabaseMissing('dikerjakan_oleh', [
            'by_spk_id' => $spk->id,
            'by_user_id' => $anggota->id,
        ]);
        $this->assertSame(1, AuditLog::where('aksi', 'anggota_tim_dihapus')->count());
        $this->assertSame(1, Notifikasi::where('user_id', $anggota->id)->where('judul', 'Dikeluarkan dari Tim')->count());
    }

    public function test_perwakilan_cannot_remove_themselves(): void
    {
        $admin = User::factory()->admin()->create();
        $petugas = User::factory()->create();
        $this->actingAs($petugas);

        $rambuPasang = $this->makeRambuPasang($admin);
        $spk = $rambuPasang->spk;

        $perwakilanRow = DikerjakanOleh::create([
            'by_spk_id' => $spk->id,
            'by_user_id' => $petugas->id,
            'is_perwakilan' => true,
        ]);

        Livewire::test(UserSpkShowComponent::class, ['spk' => $spk])
            ->call('hapusAnggota', $perwakilanRow->id);

        $this->assertDatabaseHas('dikerjakan_oleh', [
            'id' => $perwakilanRow->id,
            'by_spk_id' => $spk->id,
            'by_user_id' => $petugas->id,
        ]);
    }

    public function test_hapus_anggota_shows_warning_when_member_already_removed(): void
    {
        $admin = User::factory()->admin()->create();
        $petugas = User::factory()->create();
        $this->actingAs($petugas);

        $rambuPasang = $this->makeRambuPasang($admin);
        $spk = $rambuPasang->spk;

        DikerjakanOleh::create(['by_spk_id' => $spk->id, 'by_user_id' => $petugas->id, 'is_perwakilan' => true]);

        // Simulates another session already removing this member (or it
        // never existing) rather than actually deleting it here, since the
        // point is exercising the "not found" branch, not the happy path.
        $sudahHilangId = 99999;

        Livewire::test(UserSpkShowComponent::class, ['spk' => $spk])
            ->call('hapusAnggota', $sudahHilangId)
            ->assertDispatched('toast-show', fn ($name, $params) => $params['dataset']['variant'] === 'warning');
    }

    public function test_hapus_anggota_blocked_once_spk_is_no_longer_aktif(): void
    {
        $admin = User::factory()->admin()->create();
        $petugas = User::factory()->create();
        $anggota = User::factory()->create();
        $this->actingAs($petugas);

        $rambuPasang = $this->makeRambuPasang($admin);
        $spk = $rambuPasang->spk;
        $spk->update(['status' => 'selesai']);

        DikerjakanOleh::create(['by_spk_id' => $spk->id, 'by_user_id' => $petugas->id, 'is_perwakilan' => true]);
        $anggotaRow = DikerjakanOleh::create(['by_spk_id' => $spk->id, 'by_user_id' => $anggota->id, 'is_perwakilan' => false]);

        Livewire::test(UserSpkShowComponent::class, ['spk' => $spk])
            ->call('hapusAnggota', $anggotaRow->id)
            ->assertDispatched('toast-show', fn ($name, $params) => $params['dataset']['variant'] === 'danger');

        $this->assertDatabaseHas('dikerjakan_oleh', ['id' => $anggotaRow->id]);
    }

    public function test_tambah_anggota_blocked_once_spk_is_no_longer_aktif(): void
    {
        $admin = User::factory()->admin()->create();
        $petugas = User::factory()->create();
        $anggotaBaru = User::factory()->create();
        $this->actingAs($petugas);

        $rambuPasang = $this->makeRambuPasang($admin);
        $spk = $rambuPasang->spk;
        $spk->update(['status' => 'selesai']);

        DikerjakanOleh::create(['by_spk_id' => $spk->id, 'by_user_id' => $petugas->id, 'is_perwakilan' => true]);

        Livewire::test(UserSpkShowComponent::class, ['spk' => $spk])
            ->set('anggotaIds', [(string) $anggotaBaru->id])
            ->call('tambahAnggota')
            ->assertDispatched('toast-show', fn ($name, $params) => $params['dataset']['variant'] === 'danger');

        $this->assertDatabaseMissing('dikerjakan_oleh', [
            'by_spk_id' => $spk->id,
            'by_user_id' => $anggotaBaru->id,
        ]);
    }

    public function test_daftarkan_tim_blocked_once_spk_is_no_longer_aktif(): void
    {
        $admin = User::factory()->admin()->create();
        $petugas = User::factory()->create();
        $this->actingAs($petugas);

        // A rambu can be individually batal'd without anyone ever joining
        // the team, which can leave an SPK Selesai with zero team rows —
        // "Daftarkan Tim" must not still be usable on it.
        $rambuPasang = $this->makeRambuPasang($admin);
        $spk = $rambuPasang->spk;
        $spk->update(['status' => 'selesai']);

        Livewire::test(UserSpkShowComponent::class, ['spk' => $spk])
            ->call('daftarkanTim')
            ->assertDispatched('toast-show', fn ($name, $params) => $params['dataset']['variant'] === 'danger');

        $this->assertDatabaseMissing('dikerjakan_oleh', [
            'by_spk_id' => $spk->id,
            'by_user_id' => $petugas->id,
        ]);
    }

    // daftarkanTim()/tambahAnggota() only check for an existing team-member
    // row in PHP before inserting, which is a real race between two
    // concurrent requests — the unique(by_spk_id, by_user_id) constraint on
    // dikerjakan_oleh is the actual backstop that stops a duplicate row from
    // ever landing, regardless of what the app-level check missed.
    public function test_dikerjakan_oleh_rejects_a_duplicate_member_at_the_database_level(): void
    {
        $admin = User::factory()->admin()->create();
        $petugas = User::factory()->create();

        $rambuPasang = $this->makeRambuPasang($admin);
        $spk = $rambuPasang->spk;

        DikerjakanOleh::create(['by_spk_id' => $spk->id, 'by_user_id' => $petugas->id, 'is_perwakilan' => false]);

        $this->expectException(QueryException::class);

        DikerjakanOleh::create(['by_spk_id' => $spk->id, 'by_user_id' => $petugas->id, 'is_perwakilan' => false]);
    }

    public function test_non_perwakilan_cannot_remove_members(): void
    {
        $admin = User::factory()->admin()->create();
        $petugas = User::factory()->create();
        $anggota = User::factory()->create();
        $this->actingAs($petugas);

        $rambuPasang = $this->makeRambuPasang($admin);
        $spk = $rambuPasang->spk;

        DikerjakanOleh::create([
            'by_spk_id' => $spk->id,
            'by_user_id' => User::factory()->create()->id,
            'is_perwakilan' => true,
        ]);
        DikerjakanOleh::create([
            'by_spk_id' => $spk->id,
            'by_user_id' => $petugas->id,
            'is_perwakilan' => false,
        ]);
        $anggotaRow = DikerjakanOleh::create([
            'by_spk_id' => $spk->id,
            'by_user_id' => $anggota->id,
            'is_perwakilan' => false,
        ]);

        Livewire::test(UserSpkShowComponent::class, ['spk' => $spk])
            ->call('hapusAnggota', $anggotaRow->id);

        $this->assertDatabaseHas('dikerjakan_oleh', ['id' => $anggotaRow->id]);
    }

    public function test_ajukan_laporan_akhir_requires_all_rambu_addressed(): void
    {
        $admin = User::factory()->admin()->create();
        $petugas = User::factory()->create();
        $this->actingAs($petugas);

        $rambuPasang = $this->makeRambuPasang($admin, status: 'belum');
        $spk = $rambuPasang->spk;

        DikerjakanOleh::create([
            'by_spk_id' => $spk->id,
            'by_user_id' => $petugas->id,
            'is_perwakilan' => true,
        ]);

        Livewire::test(UserSpkShowComponent::class, ['spk' => $spk])
            ->call('ajukanLaporanAkhir');

        $this->assertNull($spk->fresh()->laporan_akhir_diajukan_at);

        $rambuPasang->update(['status' => 'tertunda']);

        Livewire::test(UserSpkShowComponent::class, ['spk' => $spk])
            ->call('ajukanLaporanAkhir');

        $this->assertNotNull($spk->fresh()->laporan_akhir_diajukan_at);
        $this->assertSame(
            route('admin.validasi.show', $spk),
            Notifikasi::where('user_id', $admin->id)->where('judul', 'Laporan Akhir Masuk')->first()->url
        );
    }

    public function test_detail_surat_rambu_card_shows_foto_koordinat_and_map_links(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs(User::factory()->create());

        $rambuPasang = $this->makeRambuPasang($admin);
        $rambuPasang->update(['foto_survei' => 'rambu-pasang/survei/contoh-petugas.jpg']);
        $spk = $rambuPasang->spk;

        $response = $this->get(route('user.spk.show', $spk));

        $response->assertOk();
        $response->assertSee('rambu-pasang/survei/contoh-petugas.jpg', false);
        $response->assertSee('-3.3194,114.5908');
        $response->assertSee(route('peta', ['focus' => $rambuPasang->rambu_id]), false);
        $response->assertSee('https://www.google.com/maps/search/?api=1&query=-3.3194,114.5908');
    }

    public function test_user_spk_detail_shows_kendala_reason_for_tertunda_rambu(): void
    {
        $admin = User::factory()->admin()->create();
        $petugas = User::factory()->create();
        $this->actingAs(User::factory()->create());

        $rambuPasang = $this->makeRambuPasang($admin, status: 'tertunda');

        Kendala::create([
            'rambu_pasang_id' => $rambuPasang->id,
            'dilaporkan_oleh' => $petugas->id,
            'alasan' => 'Tiang listrik menghalangi titik pasang.',
            'foto' => 'kendala/fake.jpg',
        ]);

        $response = $this->get(route('user.spk.show', $rambuPasang->spk));

        $response->assertOk();
        $response->assertSee('Kendala yang dilaporkan');
        $response->assertSee('Tiang listrik menghalangi titik pasang.');
    }

    public function test_user_spk_detail_shows_file_referensi_link_when_present(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs(User::factory()->create());

        $rambuPasang = $this->makeRambuPasang($admin);
        $spk = $rambuPasang->spk;
        $spk->update(['file_referensi' => 'spk/referensi/contoh-surat.pdf']);

        $response = $this->get(route('user.spk.show', $spk));

        $response->assertOk();
        $response->assertSee('Lihat File Referensi');
        $response->assertSee('spk/referensi/contoh-surat.pdf', false);
    }

    public function test_petugas_can_submit_kendala_for_joined_task(): void
    {
        $admin = User::factory()->admin()->create();
        $petugas = User::factory()->create();
        $this->actingAs($petugas);

        $rambuPasang = $this->makeRambuPasang($admin);

        DikerjakanOleh::create([
            'by_spk_id' => $rambuPasang->rambu_spk_id,
            'by_user_id' => $petugas->id,
            'is_perwakilan' => true,
        ]);

        Livewire::test(KendalaComponent::class)
            ->call('selectItem', $rambuPasang->id)
            ->set('alasan', 'Akses jalan tertutup proyek lain.')
            ->set('foto', UploadedFile::fake()->image('kendala.jpg'))
            ->call('submit')
            ->assertHasNoErrors();

        $this->assertSame(1, Kendala::count());
        $this->assertNotNull(Kendala::first()->foto);
        $this->assertSame(StatusRambuPasang::Tertunda, $rambuPasang->fresh()->status);
        $this->assertSame(1, AuditLog::where('aksi', 'kendala_diajukan')->count());
        // No per-rambu notification — admin can't act until laporan akhir is
        // submitted anyway, see ajukanLaporanAkhir().
        $this->assertSame(0, Notifikasi::where('user_id', $admin->id)->count());
    }

    public function test_petugas_cannot_submit_kendala_without_foto(): void
    {
        $admin = User::factory()->admin()->create();
        $petugas = User::factory()->create();
        $this->actingAs($petugas);

        $rambuPasang = $this->makeRambuPasang($admin);

        DikerjakanOleh::create([
            'by_spk_id' => $rambuPasang->rambu_spk_id,
            'by_user_id' => $petugas->id,
            'is_perwakilan' => true,
        ]);

        Livewire::test(KendalaComponent::class)
            ->call('selectItem', $rambuPasang->id)
            ->set('alasan', 'Akses jalan tertutup proyek lain.')
            ->call('submit')
            ->assertHasErrors(['foto' => 'required']);

        $this->assertSame(0, Kendala::count());
    }

    public function test_petugas_cannot_submit_kendala_for_unjoined_task(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs(User::factory()->create());

        $rambuPasang = $this->makeRambuPasang($admin);

        Livewire::test(KendalaComponent::class)
            ->call('selectItem', $rambuPasang->id)
            ->set('alasan', 'Mencoba tanpa join.')
            ->call('submit');

        $this->assertSame(0, Kendala::count());
        $this->assertSame(StatusRambuPasang::Belum, $rambuPasang->fresh()->status);
    }

    public function test_petugas_cannot_submit_kendala_if_joined_but_not_perwakilan(): void
    {
        $admin = User::factory()->admin()->create();
        $petugas = User::factory()->create();
        $this->actingAs($petugas);

        $rambuPasang = $this->makeRambuPasang($admin);

        DikerjakanOleh::create([
            'by_spk_id' => $rambuPasang->rambu_spk_id,
            'by_user_id' => $petugas->id,
            'is_perwakilan' => false,
        ]);

        Livewire::test(KendalaComponent::class)
            ->call('selectItem', $rambuPasang->id)
            ->set('alasan', 'Mencoba tanpa jadi perwakilan.')
            ->call('submit');

        $this->assertSame(0, Kendala::count());
        $this->assertSame(StatusRambuPasang::Belum, $rambuPasang->fresh()->status);
    }

    public function test_petugas_can_submit_laporan_pengerjaan_for_joined_task(): void
    {
        $admin = User::factory()->admin()->create();
        $petugas = User::factory()->create();
        $this->actingAs($petugas);

        $rambuPasang = $this->makeRambuPasang($admin);

        DikerjakanOleh::create([
            'by_spk_id' => $rambuPasang->rambu_spk_id,
            'by_user_id' => $petugas->id,
            'is_perwakilan' => true,
        ]);

        Livewire::test(LaporanComponent::class)
            ->call('selectItem', $rambuPasang->id)
            ->set('foto_sesudah', UploadedFile::fake()->image('sesudah.jpg'))
            ->set('catatan_lapangan', 'Sudah terpasang dengan baik.')
            ->set('barangBahan.0.nama', 'Tiang')
            ->set('barangBahan.0.jumlah', 2)
            ->set('barangBahan.0.satuan', 'batang')
            ->call('submit')
            ->assertHasNoErrors();

        $laporan = LaporanPengerjaan::first();
        $this->assertNotNull($laporan);
        $this->assertSame(StatusLaporan::Diajukan, $laporan->status);
        $this->assertSame(1, $laporan->barangBahan()->count());
        $this->assertSame(StatusRambuPasang::MenungguValidasi, $rambuPasang->fresh()->status);
        $this->assertSame(1, AuditLog::where('aksi', 'laporan_dikirim')->count());
        // No per-rambu notification — admin can't act until laporan akhir is
        // submitted anyway, see ajukanLaporanAkhir().
        $this->assertSame(0, Notifikasi::where('user_id', $admin->id)->count());
    }

    public function test_koordinat_gps_with_unparsable_format_is_rejected(): void
    {
        $admin = User::factory()->admin()->create();
        $petugas = User::factory()->create();
        $this->actingAs($petugas);

        $rambuPasang = $this->makeRambuPasang($admin);

        DikerjakanOleh::create([
            'by_spk_id' => $rambuPasang->rambu_spk_id,
            'by_user_id' => $petugas->id,
            'is_perwakilan' => true,
        ]);

        Livewire::test(LaporanComponent::class)
            ->call('selectItem', $rambuPasang->id)
            ->set('foto_sesudah', UploadedFile::fake()->image('sesudah.jpg'))
            ->set('koordinat_gps', 'entah dimana')
            ->call('submit')
            ->assertHasErrors(['koordinat_gps']);

        $this->assertSame(0, LaporanPengerjaan::count());
    }

    public function test_laporan_form_shows_existing_foto_survei_as_before_reference(): void
    {
        $admin = User::factory()->admin()->create();
        $petugas = User::factory()->create();
        $this->actingAs($petugas);

        $rambuPasang = $this->makeRambuPasang($admin);
        $rambuPasang->update(['foto_survei' => 'rambu-pasang/survei/contoh-survei.jpg']);

        DikerjakanOleh::create([
            'by_spk_id' => $rambuPasang->rambu_spk_id,
            'by_user_id' => $petugas->id,
            'is_perwakilan' => true,
        ]);

        Livewire::test(LaporanComponent::class)
            ->call('selectItem', $rambuPasang->id)
            ->assertSee('rambu-pasang/survei/contoh-survei.jpg')
            ->assertSee('Foto Sebelum (dari survei SPK)');
    }

    public function test_ajukan_laporan_akhir_becomes_available_again_after_partial_reject_cycle(): void
    {
        $admin = User::factory()->admin()->create();
        $petugas = User::factory()->create();
        $this->actingAs($petugas);

        $rambuPasang1 = $this->makeRambuPasang($admin, status: 'menunggu_validasi');
        $spk = $rambuPasang1->spk;

        $jenisRambu = JenisRambu::create(['nama_jenis' => 'Rambu Larangan']);
        $rambu2 = Rambu::create([
            'jenis_rambu_id' => $jenisRambu->id,
            'wilayah' => 'Banjarmasin Tengah',
            'lokasi' => 'Simpang tiga',
            'koordinat' => '-3.30,114.60',
        ]);
        $rambuPasang2 = RambuPasang::create([
            'rambu_spk_id' => $spk->id,
            'rambu_id' => $rambu2->id,
            'jenis_pekerjaan' => 'pasang_baru',
            'jumlah' => 1,
            'status' => 'menunggu_validasi',
        ]);

        LaporanPengerjaan::create([
            'rambu_pasang_id' => $rambuPasang1->id,
            'dilaporkan_oleh' => $petugas->id,
            'foto_sesudah' => 'laporan-pengerjaan/sesudah/satu.jpg',
            'status' => 'diajukan',
        ]);
        LaporanPengerjaan::create([
            'rambu_pasang_id' => $rambuPasang2->id,
            'dilaporkan_oleh' => $petugas->id,
            'foto_sesudah' => 'laporan-pengerjaan/sesudah/dua.jpg',
            'status' => 'diajukan',
        ]);

        DikerjakanOleh::create([
            'by_spk_id' => $spk->id,
            'by_user_id' => $petugas->id,
            'is_perwakilan' => true,
        ]);

        Livewire::test(UserSpkShowComponent::class, ['spk' => $spk])
            ->call('ajukanLaporanAkhir');

        $this->assertNotNull($spk->fresh()->laporan_akhir_diajukan_at);

        // Admin accepts rambu1 (-> Selesai) and rejects rambu2 (-> Revisi) in the same validation round.
        $this->actingAs($admin);
        Livewire::test(ValidasiShowComponent::class, ['spk' => $spk])
            ->set("checked.{$rambuPasang1->id}", true)
            ->set("checked.{$rambuPasang2->id}", false)
            ->call('lanjutkan')
            ->set("catatanPenolakan.{$rambuPasang2->id}", 'Pemasangan miring, perlu diperbaiki.')
            ->call('konfirmasiPenolakan')
            ->assertHasNoErrors();

        $this->assertSame(StatusRambuPasang::Selesai, $rambuPasang1->fresh()->status);
        $this->assertSame(StatusRambuPasang::Revisi, $rambuPasang2->fresh()->status);
        $this->assertNull($spk->fresh()->laporan_akhir_diajukan_at);

        // Petugas resubmits only the revised rambu. Without the getSiapDiajukanProperty
        // fix, this would stay permanently un-submittable because rambuPasang1 is Selesai.
        $this->actingAs($petugas);
        Livewire::test(LaporanComponent::class)
            ->call('selectItem', $rambuPasang2->id)
            ->set('foto_sesudah', UploadedFile::fake()->image('sesudah-revisi.jpg'))
            ->call('submit')
            ->assertHasNoErrors();

        $this->assertSame(StatusRambuPasang::MenungguValidasi, $rambuPasang2->fresh()->status);

        Livewire::test(UserSpkShowComponent::class, ['spk' => $spk])
            ->call('ajukanLaporanAkhir');

        $this->assertNotNull($spk->fresh()->laporan_akhir_diajukan_at);
    }

    public function test_catatan_penolakan_shown_on_spk_show_page_when_revisi(): void
    {
        $admin = User::factory()->admin()->create();
        $petugas = User::factory()->create();
        $this->actingAs($petugas);

        $rambuPasang = $this->makeRambuPasang($admin, status: 'revisi');
        LaporanPengerjaan::create([
            'rambu_pasang_id' => $rambuPasang->id,
            'dilaporkan_oleh' => $petugas->id,
            'foto_sesudah' => 'laporan-pengerjaan/sesudah/lama.jpg',
            'status' => StatusLaporan::Ditolak->value,
            'catatan_penolakan' => 'Pemasangan miring, perlu diperbaiki.',
        ]);

        $response = $this->get(route('user.spk.show', $rambuPasang->spk));

        $response->assertOk();
        $response->assertSee('Pemasangan miring, perlu diperbaiki.');
    }

    public function test_user_spk_detail_shows_tanggal_dan_petugas_survei(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs(User::factory()->create());

        $rambuPasang = $this->makeRambuPasang($admin);
        $rambuPasang->spk->update(['tanggal_survei' => '2026-06-15', 'petugas_survei' => 'Budi, Andi']);

        $response = $this->get(route('user.spk.show', $rambuPasang->spk));

        $response->assertOk();
        $response->assertSee('Budi, Andi');
    }

    public function test_user_spk_detail_shows_durasi_dan_selisih_deadline_when_terlambat(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs(User::factory()->create());

        $rambuPasang = $this->makeRambuPasang($admin);
        $spk = $rambuPasang->spk;
        $spk->update(['status' => 'selesai', 'deadline' => now()->subDays(3)]);
        $spk->created_at = now()->subDays(8);
        $spk->selesai_pada = now();
        $spk->save();

        $this->assertSame(8, $spk->durasiPengerjaanHari());
        $this->assertSame(3, $spk->selisihDeadlineHari());
        $this->assertSame('Terlambat 3 hari dari deadline', $spk->selisihDeadlineLabel());

        $response = $this->get(route('user.spk.show', $spk));

        $response->assertOk();
        $response->assertSee('Terlambat 3 hari dari deadline');
    }

    public function test_user_spk_detail_shows_contact_person_when_present(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs(User::factory()->create());

        $rambuPasang = $this->makeRambuPasang($admin);

        ContactPerson::create([
            'nama_lengkap' => 'Abdul',
            'no_telepon' => '08226735526',
            'contact_person_spk_id' => $rambuPasang->rambu_spk_id,
        ]);

        $response = $this->get(route('user.spk.show', $rambuPasang->spk));

        $response->assertOk();
        $response->assertSee('Contact Person');
        $response->assertSee('Abdul (08226735526)');
    }

    public function test_petugas_cannot_submit_laporan_if_joined_but_not_perwakilan(): void
    {
        $admin = User::factory()->admin()->create();
        $petugas = User::factory()->create();
        $this->actingAs($petugas);

        $rambuPasang = $this->makeRambuPasang($admin);

        DikerjakanOleh::create([
            'by_spk_id' => $rambuPasang->rambu_spk_id,
            'by_user_id' => $petugas->id,
            'is_perwakilan' => false,
        ]);

        Livewire::test(LaporanComponent::class)
            ->call('selectItem', $rambuPasang->id)
            ->set('foto_sesudah', UploadedFile::fake()->image('sesudah.jpg'))
            ->call('submit');

        $this->assertSame(0, LaporanPengerjaan::count());
        $this->assertSame(StatusRambuPasang::Belum, $rambuPasang->fresh()->status);
    }

    public function test_unjoined_petugas_sees_disabled_download_link_and_gets_a_toast_instead_of_the_pdf(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs(User::factory()->create());

        $rambuPasang = $this->makeRambuPasang($admin);

        Livewire::test(UserSpkShowComponent::class, ['spk' => $rambuPasang->spk])
            ->assertDontSeeHtml('href="'.route('spk.surat-pengantar', $rambuPasang->rambu_spk_id).'"')
            ->call('tautanSuratPengantarDitolak')
            ->assertDispatched('toast-show');
    }

    public function test_joined_petugas_sees_a_working_download_link(): void
    {
        $admin = User::factory()->admin()->create();
        $petugas = User::factory()->create();
        $this->actingAs($petugas);

        $rambuPasang = $this->makeRambuPasang($admin);

        DikerjakanOleh::create([
            'by_spk_id' => $rambuPasang->rambu_spk_id,
            'by_user_id' => $petugas->id,
            'is_perwakilan' => true,
        ]);

        Livewire::test(UserSpkShowComponent::class, ['spk' => $rambuPasang->spk])
            ->assertSeeHtml('href="'.route('spk.surat-pengantar', $rambuPasang->rambu_spk_id).'"');
    }
}
