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
        // Petugas has no dedicated "my temuan" page to link to, so this notification stays unclickable.
        $this->assertNull(Notifikasi::where('user_id', $petugas->id)->first()->url);
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
}
