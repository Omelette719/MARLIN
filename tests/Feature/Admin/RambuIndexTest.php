<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\Rambu\Index as RambuIndexComponent;
use App\Models\JenisRambu;
use App\Models\Rambu;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RambuIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_petugas_cannot_access_daftar_rambu(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(route('admin.rambu.index'))->assertRedirect(route('dashboard'));
    }

    public function test_admin_can_view_daftar_rambu(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $jenis = JenisRambu::create(['nama_jenis' => 'Rambu Peringatan']);
        Rambu::create([
            'jenis_rambu_id' => $jenis->id,
            'wilayah' => 'Banjarmasin Tengah',
            'lokasi' => 'Depan pasar lama',
            'koordinat' => '-3.30,114.59',
        ]);

        $response = $this->get(route('admin.rambu.index'));

        $response->assertOk();
        $response->assertSee('Depan pasar lama');
    }

    public function test_daftar_rambu_filters_by_jenis(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $jenisA = JenisRambu::create(['nama_jenis' => 'Rambu Larangan']);
        $jenisB = JenisRambu::create(['nama_jenis' => 'Rambu Perintah']);

        Rambu::create(['jenis_rambu_id' => $jenisA->id, 'wilayah' => 'Banjarmasin Tengah', 'lokasi' => 'Lokasi A', 'koordinat' => '-3.30,114.59']);
        Rambu::create(['jenis_rambu_id' => $jenisB->id, 'wilayah' => 'Banjarmasin Utara', 'lokasi' => 'Lokasi B', 'koordinat' => '-3.29,114.60']);

        Livewire::test(RambuIndexComponent::class, ['jenis' => (string) $jenisA->id])
            ->assertSee('Lokasi A')
            ->assertDontSee('Lokasi B');
    }

    public function test_daftar_rambu_search_filters_by_wilayah_or_lokasi(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $jenis = JenisRambu::create(['nama_jenis' => 'Rambu Peringatan']);
        Rambu::create(['jenis_rambu_id' => $jenis->id, 'wilayah' => 'Banjarmasin Tengah', 'lokasi' => 'Depan pasar lama', 'koordinat' => '-3.30,114.59']);
        Rambu::create(['jenis_rambu_id' => $jenis->id, 'wilayah' => 'Banjarmasin Utara', 'lokasi' => 'Simpang tiga', 'koordinat' => '-3.29,114.60']);

        Livewire::test(RambuIndexComponent::class)
            ->set('search', 'pasar lama')
            ->assertSee('Depan pasar lama')
            ->assertDontSee('Simpang tiga');
    }

    public function test_daftar_rambu_filters_by_kondisi(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $jenis = JenisRambu::create(['nama_jenis' => 'Rambu Peringatan']);
        Rambu::create([
            'jenis_rambu_id' => $jenis->id,
            'wilayah' => 'Banjarmasin Tengah',
            'lokasi' => 'Lokasi Baik',
            'koordinat' => '-3.30,114.59',
            'sudah_terpasang' => true,
            'kondisi_terkini' => 'baik',
        ]);
        Rambu::create([
            'jenis_rambu_id' => $jenis->id,
            'wilayah' => 'Banjarmasin Utara',
            'lokasi' => 'Lokasi Rusak',
            'koordinat' => '-3.29,114.60',
            'sudah_terpasang' => true,
            'kondisi_terkini' => 'rusak',
        ]);

        Livewire::test(RambuIndexComponent::class)
            ->set('kondisi', 'rusak')
            ->assertSee('Lokasi Rusak')
            ->assertDontSee('Lokasi Baik');
    }

    public function test_daftar_rambu_kondisi_filter_excludes_not_yet_installed_signs(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $jenis = JenisRambu::create(['nama_jenis' => 'Rambu Peringatan']);
        // sudah_terpasang defaults to false, kondisi_terkini defaults to
        // "baik" at the DB level even though the table shows "N/A" for it —
        // the Kondisi filter should follow what's shown, not the raw column.
        Rambu::create([
            'jenis_rambu_id' => $jenis->id,
            'wilayah' => 'Banjarmasin Tengah',
            'lokasi' => 'Lokasi Belum Terpasang',
            'koordinat' => '-3.30,114.59',
        ]);

        Livewire::test(RambuIndexComponent::class)
            ->set('kondisi', 'baik')
            ->assertDontSee('Lokasi Belum Terpasang');
    }

    public function test_daftar_rambu_filters_by_status_terpasang(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $jenis = JenisRambu::create(['nama_jenis' => 'Rambu Peringatan']);
        Rambu::create([
            'jenis_rambu_id' => $jenis->id,
            'wilayah' => 'Banjarmasin Tengah',
            'lokasi' => 'Lokasi Terpasang',
            'koordinat' => '-3.30,114.59',
            'sudah_terpasang' => true,
        ]);
        Rambu::create([
            'jenis_rambu_id' => $jenis->id,
            'wilayah' => 'Banjarmasin Utara',
            'lokasi' => 'Lokasi Belum Terpasang',
            'koordinat' => '-3.29,114.60',
            'sudah_terpasang' => false,
        ]);

        Livewire::test(RambuIndexComponent::class)
            ->set('status', 'terpasang')
            ->assertSee('Lokasi Terpasang')
            ->assertDontSee('Lokasi Belum Terpasang');

        Livewire::test(RambuIndexComponent::class)
            ->set('status', 'belum_terpasang')
            ->assertSee('Lokasi Belum Terpasang')
            ->assertDontSee('Lokasi Terpasang');
    }

    public function test_petugas_can_view_shared_daftar_rambu_read_only(): void
    {
        $this->actingAs(User::factory()->create());

        $jenis = JenisRambu::create(['nama_jenis' => 'Rambu Peringatan']);
        Rambu::create(['jenis_rambu_id' => $jenis->id, 'wilayah' => 'Banjarmasin Tengah', 'lokasi' => 'Depan pasar lama', 'koordinat' => '-3.30,114.59']);

        $response = $this->get(route('rambu.index'));

        $response->assertOk();
        $response->assertSee('Depan pasar lama');
    }

    public function test_daftar_rambu_rows_link_to_detail_peta_and_google_maps(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $jenis = JenisRambu::create(['nama_jenis' => 'Rambu Peringatan']);
        $rambu = Rambu::create([
            'jenis_rambu_id' => $jenis->id,
            'wilayah' => 'Banjarmasin Tengah',
            'lokasi' => 'Depan pasar lama',
            'koordinat' => '-3.30,114.59',
        ]);

        $response = $this->get(route('admin.rambu.index'));

        $response->assertOk();
        $response->assertSee(route('rambu.show', $rambu), false);
        $response->assertSee(route('peta', ['focus' => $rambu->id]), false);
        $response->assertSee('https://www.google.com/maps/search/?api=1&query=-3.30,114.59');
    }
}
