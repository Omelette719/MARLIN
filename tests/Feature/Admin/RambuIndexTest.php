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
