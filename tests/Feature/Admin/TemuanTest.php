<?php

namespace Tests\Feature\Admin;

use App\Enums\JenisPekerjaan;
use App\Enums\StatusTindakLanjut;
use App\Livewire\Admin\Spk\Create as SpkCreateComponent;
use App\Livewire\Admin\Temuan\Index as TemuanIndexComponent;
use App\Models\AuditLog;
use App\Models\JenisRambu;
use App\Models\LaporanKondisi;
use App\Models\Notifikasi;
use App\Models\Rambu;
use App\Models\RambuPasang;
use App\Models\Spk;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class TemuanTest extends TestCase
{
    use RefreshDatabase;

    private function makeTemuan(User $petugas): LaporanKondisi
    {
        $jenisRambu = JenisRambu::create(['nama_jenis' => 'Rambu Larangan']);
        $rambu = Rambu::create([
            'jenis_rambu_id' => $jenisRambu->id,
            'jalan' => 'Ahmad Yani',
            'rt' => '12',
            'lokasi' => 'Depan pasar',
            'koordinat' => '-3.30,114.59',
            'kondisi_terkini' => 'rusak',
            'sudah_terpasang' => true,
        ]);

        return LaporanKondisi::create([
            'rambu_id' => $rambu->id,
            'dilaporkan_oleh' => $petugas->id,
            'kondisi_dilaporkan' => 'rusak',
            'catatan' => 'Tiang bengkok.',
        ]);
    }

    public function test_admin_sees_only_unhandled_temuan(): void
    {
        $admin = User::factory()->admin()->create();
        $petugas = User::factory()->create();
        $this->actingAs($admin);

        $temuan = $this->makeTemuan($petugas);
        $handled = $this->makeTemuan($petugas);
        $handled->update(['status_tindak_lanjut' => StatusTindakLanjut::SudahDibuatkanSpk]);

        $response = $this->get(route('admin.temuan.index'));
        $response->assertOk();
        $response->assertSee($temuan->rambu->lokasi);
    }

    // Each "Tolak" modal used to share DOM identity with whatever ended up
    // at the same position after a list re-render (no wire:key once the
    // modal was moved out of its per-item card to fix a grid-layout bug),
    // so confirming/canceling one could leave a stale modal open showing
    // a different temuan, or make the next one refuse to open at all.
    // Asserting the key exists per item is the closest a server-rendered
    // HTML assertion can get to covering that DOM-identity bug.
    public function test_admin_temuan_page_keys_each_reject_modal_by_id(): void
    {
        $admin = User::factory()->admin()->create();
        $petugas = User::factory()->create();
        $this->actingAs($admin);

        $satu = $this->makeTemuan($petugas);
        $dua = $this->makeTemuan($petugas);

        $response = $this->get(route('admin.temuan.index'));

        $response->assertOk();
        $response->assertSee('wire:key="tolak-temuan-modal-'.$satu->id.'"', false);
        $response->assertSee('wire:key="tolak-temuan-modal-'.$dua->id.'"', false);
    }

    public function test_admin_temuan_page_shows_the_reported_photo(): void
    {
        $admin = User::factory()->admin()->create();
        $petugas = User::factory()->create();
        $this->actingAs($admin);

        $temuan = $this->makeTemuan($petugas);
        $temuan->update(['foto' => 'laporan-kondisi/contoh-temuan.jpg']);

        $response = $this->get(route('admin.temuan.index'));

        $response->assertOk();
        $response->assertSee('laporan-kondisi/contoh-temuan.jpg', false);
    }

    public function test_admin_can_reject_temuan(): void
    {
        $admin = User::factory()->admin()->create();
        $petugas = User::factory()->create();
        $this->actingAs($admin);

        $temuan = $this->makeTemuan($petugas);

        Livewire::test(TemuanIndexComponent::class)
            ->call('tolak', $temuan->id);

        $this->assertSame(StatusTindakLanjut::Ditolak, $temuan->fresh()->status_tindak_lanjut);
        $this->assertSame(1, AuditLog::where('aksi', 'temuan_ditolak')->count());
        $this->assertSame(1, Notifikasi::where('user_id', $petugas->id)->count());
        // Petugas has no dedicated "my temuan" page, so this points at the
        // rambu itself instead — its Riwayat Temuan Kondisi section shows
        // this same (now-rejected) report in context.
        $this->assertSame(
            route('rambu.show', $temuan->rambu),
            Notifikasi::where('user_id', $petugas->id)->first()->url
        );
    }

    public function test_admin_creating_spk_from_temuan_prefills_perbaikan_and_marks_handled(): void
    {
        $admin = User::factory()->admin()->create();
        $petugas = User::factory()->create();
        $this->actingAs($admin);

        $temuan = $this->makeTemuan($petugas);
        $rambu = $temuan->rambu;

        Livewire::withQueryParams(['laporan_kondisi' => $temuan->id])
            ->test(SpkCreateComponent::class)
            ->assertSet('jalan', $rambu->jalan)
            ->assertSet('rt', $rambu->rt)
            ->assertSet('jenis_spk', JenisPekerjaan::Perbaikan->value)
            ->assertSet('rambuItems.0.rambu_id', (string) $rambu->id)
            ->set('kelurahan', 'Kertak Baru')
            ->set('deadline', now()->addDays(5)->toDateString())
            ->set('asal_permintaan', 'laporan_masyarakat')
            ->set('rambuItems.0.jumlah', 1)
            ->call('save')
            ->assertHasNoErrors();

        $spk = Spk::first();
        $this->assertNotNull($spk);

        $rambuPasang = RambuPasang::where('rambu_spk_id', $spk->id)->first();
        $this->assertSame(JenisPekerjaan::Perbaikan, $rambuPasang->jenis_pekerjaan);
        $this->assertSame($temuan->id, $rambuPasang->laporan_kondisi_id);

        $this->assertSame(StatusTindakLanjut::SudahDibuatkanSpk, $temuan->fresh()->status_tindak_lanjut);

        $this->assertSame(1, Rambu::count());
    }

    // The temuan already has photo evidence of the damage — Create.php used
    // to always set foto_survei to null when pre-filling from a temuan, so
    // admin had to re-upload a photo that already existed, and the new SPK
    // started with no cover photo at all until they did.
    public function test_admin_creating_spk_from_temuan_copies_the_temuan_photo_as_foto_survei(): void
    {
        Storage::fake('public');

        $admin = User::factory()->admin()->create();
        $petugas = User::factory()->create();
        $this->actingAs($admin);

        $temuan = $this->makeTemuan($petugas);
        Storage::disk('public')->put('laporan-kondisi/temuan-asli.jpg', 'isi-foto-temuan');
        $temuan->update(['foto' => 'laporan-kondisi/temuan-asli.jpg']);

        Livewire::withQueryParams(['laporan_kondisi' => $temuan->id])
            ->test(SpkCreateComponent::class)
            ->assertSet('rambuItems.0.foto_survei_existing', 'laporan-kondisi/temuan-asli.jpg')
            ->set('kelurahan', 'Kertak Baru')
            ->set('deadline', now()->addDays(5)->toDateString())
            ->set('asal_permintaan', 'laporan_masyarakat')
            ->set('rambuItems.0.jumlah', 1)
            ->call('save')
            ->assertHasNoErrors();

        $rambuPasang = RambuPasang::first();

        $this->assertNotNull($rambuPasang->foto_survei);
        // Copied into its own file, not just the same stored path reused.
        $this->assertNotSame($temuan->foto, $rambuPasang->foto_survei);
        Storage::disk('public')->assertExists($rambuPasang->foto_survei);
        $this->assertSame(
            Storage::disk('public')->get('laporan-kondisi/temuan-asli.jpg'),
            Storage::disk('public')->get($rambuPasang->foto_survei)
        );
    }

    public function test_admin_creating_spk_from_temuan_prefers_a_freshly_uploaded_photo_over_the_temuan_photo(): void
    {
        Storage::fake('public');

        $admin = User::factory()->admin()->create();
        $petugas = User::factory()->create();
        $this->actingAs($admin);

        $temuan = $this->makeTemuan($petugas);
        Storage::disk('public')->put('laporan-kondisi/temuan-asli.jpg', 'isi-foto-temuan');
        $temuan->update(['foto' => 'laporan-kondisi/temuan-asli.jpg']);

        Livewire::withQueryParams(['laporan_kondisi' => $temuan->id])
            ->test(SpkCreateComponent::class)
            ->set('kelurahan', 'Kertak Baru')
            ->set('deadline', now()->addDays(5)->toDateString())
            ->set('asal_permintaan', 'laporan_masyarakat')
            ->set('rambuItems.0.jumlah', 1)
            ->set('rambuItems.0.foto_survei', UploadedFile::fake()->image('baru.jpg'))
            ->call('save')
            ->assertHasNoErrors();

        $rambuPasang = RambuPasang::first();

        $this->assertNotNull($rambuPasang->foto_survei);
        Storage::disk('public')->assertExists($rambuPasang->foto_survei);
        $this->assertNotSame(
            Storage::disk('public')->get('laporan-kondisi/temuan-asli.jpg'),
            Storage::disk('public')->get($rambuPasang->foto_survei)
        );
    }
}
