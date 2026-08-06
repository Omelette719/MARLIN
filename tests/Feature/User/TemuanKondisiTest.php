<?php

namespace Tests\Feature\User;

use App\Enums\KondisiRambu;
use App\Livewire\User\Temuan as TemuanComponent;
use App\Models\AuditLog;
use App\Models\JenisRambu;
use App\Models\LaporanKondisi;
use App\Models\Notifikasi;
use App\Models\Rambu;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use Tests\TestCase;

class TemuanKondisiTest extends TestCase
{
    use RefreshDatabase;

    public function test_petugas_can_submit_temuan_kondisi(): void
    {
        $admin1 = User::factory()->admin()->create();
        $admin2 = User::factory()->admin()->create();
        $petugas = User::factory()->create();
        $this->actingAs($petugas);

        $jenisRambu = JenisRambu::create(['nama_jenis' => 'Rambu Larangan']);
        $rambu = Rambu::create([
            'jenis_rambu_id' => $jenisRambu->id,
            'wilayah' => 'Banjarmasin Utara',
            'lokasi' => 'Depan pasar',
            'koordinat' => '-3.30,114.59',
            'kondisi_terkini' => 'baik',
        ]);

        Livewire::test(TemuanComponent::class)
            ->set('rambu_id', (string) $rambu->id)
            ->set('kondisi_dilaporkan', 'rusak')
            ->set('catatan', 'Tiang bengkok akibat tertabrak.')
            ->set('foto', UploadedFile::fake()->image('temuan.jpg'))
            ->call('submit')
            ->assertHasNoErrors();

        $this->assertSame(1, LaporanKondisi::count());

        $laporan = LaporanKondisi::first();
        $this->assertSame(KondisiRambu::Rusak, $laporan->kondisi_dilaporkan);
        $this->assertSame('baru', $laporan->status_tindak_lanjut->value);
        $this->assertSame($petugas->id, $laporan->dilaporkan_oleh);
        $this->assertNotNull($laporan->foto);

        $this->assertSame(KondisiRambu::Rusak, $rambu->fresh()->kondisi_terkini);
        $this->assertSame(1, AuditLog::where('aksi', 'laporan_kondisi_dibuat')->count());
        $this->assertSame(2, Notifikasi::whereIn('user_id', [$admin1->id, $admin2->id])->count());
        $this->assertSame(route('admin.temuan.index'), Notifikasi::where('user_id', $admin1->id)->first()->url);
        $this->assertSame($laporan->foto, Notifikasi::where('user_id', $admin1->id)->first()->foto);
    }

    public function test_non_image_foto_is_rejected_immediately_on_upload(): void
    {
        $this->actingAs(User::factory()->create());

        $jenisRambu = JenisRambu::create(['nama_jenis' => 'Rambu Larangan']);
        $rambu = Rambu::create([
            'jenis_rambu_id' => $jenisRambu->id,
            'wilayah' => 'Banjarmasin Utara',
            'lokasi' => 'Depan pasar',
            'koordinat' => '-3.30,114.59',
        ]);

        Livewire::test(TemuanComponent::class)
            ->set('rambu_id', (string) $rambu->id)
            ->set('foto', UploadedFile::fake()->create('dokumen.pdf', 100, 'application/pdf'))
            ->assertSet('foto', null)
            ->assertHasErrors(['foto']);
    }

    public function test_petugas_cannot_submit_temuan_without_foto(): void
    {
        $this->actingAs(User::factory()->create());

        $jenisRambu = JenisRambu::create(['nama_jenis' => 'Rambu Larangan']);
        $rambu = Rambu::create([
            'jenis_rambu_id' => $jenisRambu->id,
            'wilayah' => 'Banjarmasin Utara',
            'lokasi' => 'Depan pasar',
            'koordinat' => '-3.30,114.59',
        ]);

        Livewire::test(TemuanComponent::class)
            ->set('rambu_id', (string) $rambu->id)
            ->set('kondisi_dilaporkan', 'rusak')
            ->call('submit')
            ->assertHasErrors(['foto' => 'required']);

        $this->assertSame(0, LaporanKondisi::count());
    }

    // A too-large image passes RejectsNonImageUploads' immediate check (it
    // only looks at mime type, not size) and only fails later at submit()'s
    // own validate() — <x-photo-upload> never forwarded label/description
    // to its inner <flux:input>, so Flux's automatic error text never had
    // a reason to render; the failure was real but invisible.
    public function test_oversized_foto_shows_a_visible_error_on_submit(): void
    {
        $this->actingAs(User::factory()->create());

        $jenisRambu = JenisRambu::create(['nama_jenis' => 'Rambu Larangan']);
        $rambu = Rambu::create([
            'jenis_rambu_id' => $jenisRambu->id,
            'wilayah' => 'Banjarmasin Utara',
            'lokasi' => 'Depan pasar',
            'koordinat' => '-3.30,114.59',
        ]);

        Livewire::test(TemuanComponent::class)
            ->set('rambu_id', (string) $rambu->id)
            ->set('kondisi_dilaporkan', 'rusak')
            ->set('foto', UploadedFile::fake()->image('temuan.jpg')->size(6000))
            ->call('submit')
            ->assertHasErrors(['foto'])
            ->assertSee('The foto field must not be greater than 5120 kilobytes.');

        $this->assertSame(0, LaporanKondisi::count());
    }

    public function test_temuan_form_preselects_rambu_from_query_string(): void
    {
        $this->actingAs(User::factory()->create());

        $jenisRambu = JenisRambu::create(['nama_jenis' => 'Rambu Larangan']);
        $rambu = Rambu::create([
            'jenis_rambu_id' => $jenisRambu->id,
            'wilayah' => 'Banjarmasin Utara',
            'lokasi' => 'Depan pasar',
            'koordinat' => '-3.30,114.59',
        ]);

        Livewire::test(TemuanComponent::class, ['rambu_id' => (string) $rambu->id])
            ->assertSet('rambu_id', (string) $rambu->id);
    }
}
