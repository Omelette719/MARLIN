<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\JenisRambu\Index as JenisRambuIndexComponent;
use App\Models\JenisRambu;
use App\Models\Rambu;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class JenisRambuTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_jenis_rambu(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(JenisRambuIndexComponent::class)
            ->set('nama_jenis', 'Rambu Petunjuk')
            ->set('spesifikasi_standar', 'Dasar hijau/biru.')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('jenis_rambu', ['nama_jenis' => 'Rambu Petunjuk']);
    }

    public function test_admin_can_edit_jenis_rambu(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $jenis = JenisRambu::create(['nama_jenis' => 'Lama']);

        Livewire::test(JenisRambuIndexComponent::class)
            ->call('edit', $jenis->id)
            ->set('nama_jenis', 'Baru')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('Baru', $jenis->fresh()->nama_jenis);
    }

    public function test_nama_jenis_with_numbers_or_symbols_is_rejected(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(JenisRambuIndexComponent::class)
            ->set('nama_jenis', 'Rambu Tipe-2!')
            ->call('save')
            ->assertHasErrors(['nama_jenis' => 'regex']);

        $this->assertDatabaseMissing('jenis_rambu', ['nama_jenis' => 'Rambu Tipe-2!']);
    }

    public function test_admin_can_delete_unused_jenis_rambu(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $jenis = JenisRambu::create(['nama_jenis' => 'Tidak Terpakai']);

        Livewire::test(JenisRambuIndexComponent::class)->call('hapus', $jenis->id);

        $this->assertDatabaseMissing('jenis_rambu', ['id' => $jenis->id]);
    }

    public function test_admin_cannot_delete_jenis_rambu_still_in_use(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $jenis = JenisRambu::create(['nama_jenis' => 'Terpakai']);
        Rambu::create([
            'jenis_rambu_id' => $jenis->id,
            'wilayah' => 'Banjarmasin Tengah',
            'lokasi' => 'Depan pasar',
            'koordinat' => '-3.30,114.59',
        ]);

        Livewire::test(JenisRambuIndexComponent::class)->call('hapus', $jenis->id);

        $this->assertDatabaseHas('jenis_rambu', ['id' => $jenis->id]);
    }

    public function test_petugas_can_view_shared_jenis_rambu_read_only(): void
    {
        $this->actingAs(User::factory()->create());

        JenisRambu::create(['nama_jenis' => 'Rambu Larangan']);

        $response = $this->get(route('jenis-rambu.index'));

        $response->assertOk();
        $response->assertSee('Rambu Larangan');
        $response->assertDontSee('Tambah Jenis Rambu');
    }

    public function test_petugas_cannot_mutate_jenis_rambu(): void
    {
        $this->actingAs(User::factory()->create());

        $jenis = JenisRambu::create(['nama_jenis' => 'Rambu Larangan']);

        Livewire::test(JenisRambuIndexComponent::class)
            ->call('tambahBaru')
            ->assertStatus(403);

        Livewire::test(JenisRambuIndexComponent::class)
            ->call('edit', $jenis->id)
            ->assertStatus(403);

        Livewire::test(JenisRambuIndexComponent::class)
            ->call('hapus', $jenis->id)
            ->assertStatus(403);

        $this->assertDatabaseHas('jenis_rambu', ['id' => $jenis->id]);
    }

    public function test_jenis_rambu_card_shows_rambu_count(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $jenis = JenisRambu::create(['nama_jenis' => 'Rambu Larangan']);
        Rambu::create(['jenis_rambu_id' => $jenis->id, 'wilayah' => 'Banjarmasin Tengah', 'lokasi' => 'Depan pasar', 'koordinat' => '-3.30,114.59']);
        Rambu::create(['jenis_rambu_id' => $jenis->id, 'wilayah' => 'Banjarmasin Utara', 'lokasi' => 'Simpang tiga', 'koordinat' => '-3.29,114.60']);

        $response = $this->get(route('admin.jenis-rambu.index'));

        $response->assertOk();
        $response->assertSee('Rambu Larangan');
        $response->assertSee('2 rambu terdaftar');
    }

    public function test_clicking_jenis_rambu_card_redirects_to_filtered_rambu_list(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $jenis = JenisRambu::create(['nama_jenis' => 'Rambu Larangan']);

        Livewire::test(JenisRambuIndexComponent::class)
            ->call('lihatRambu', $jenis->id)
            ->assertRedirect(route('admin.rambu.index', ['jenis' => $jenis->id]));
    }
}
