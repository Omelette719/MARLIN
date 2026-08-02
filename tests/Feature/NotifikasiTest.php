<?php

namespace Tests\Feature;

use App\Livewire\Notifikasi as NotifikasiComponent;
use App\Models\Notifikasi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class NotifikasiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_only_sees_own_notifikasi(): void
    {
        $me = User::factory()->create();
        $other = User::factory()->create();
        $this->actingAs($me);

        Notifikasi::create(['user_id' => $me->id, 'judul' => 'Punyaku', 'pesan' => 'isi', 'dibaca' => false]);
        Notifikasi::create(['user_id' => $other->id, 'judul' => 'Bukan Punyaku', 'pesan' => 'isi', 'dibaca' => false]);

        $response = $this->get(route('notifikasi'));
        $response->assertOk();
        $response->assertSee('Punyaku');
        $response->assertDontSee('Bukan Punyaku');
    }

    public function test_user_can_mark_single_notifikasi_as_read(): void
    {
        $me = User::factory()->create();
        $this->actingAs($me);

        $notif = Notifikasi::create(['user_id' => $me->id, 'judul' => 'Judul', 'pesan' => 'isi', 'dibaca' => false]);

        Livewire::test(NotifikasiComponent::class)->call('tandaiDibaca', $notif->id);

        $this->assertTrue($notif->fresh()->dibaca);
    }

    public function test_user_cannot_mark_other_users_notifikasi_as_read(): void
    {
        $me = User::factory()->create();
        $other = User::factory()->create();
        $this->actingAs($me);

        $notif = Notifikasi::create(['user_id' => $other->id, 'judul' => 'Judul', 'pesan' => 'isi', 'dibaca' => false]);

        Livewire::test(NotifikasiComponent::class)->call('tandaiDibaca', $notif->id);

        $this->assertFalse($notif->fresh()->dibaca);
    }

    public function test_user_can_mark_all_as_read(): void
    {
        $me = User::factory()->create();
        $this->actingAs($me);

        Notifikasi::create(['user_id' => $me->id, 'judul' => 'A', 'pesan' => 'isi', 'dibaca' => false]);
        Notifikasi::create(['user_id' => $me->id, 'judul' => 'B', 'pesan' => 'isi', 'dibaca' => false]);

        Livewire::test(NotifikasiComponent::class)->call('tandaiSemuaDibaca');

        $this->assertSame(0, Notifikasi::where('user_id', $me->id)->where('dibaca', false)->count());
    }

    public function test_buka_notifikasi_marks_read_and_redirects_to_its_url(): void
    {
        $me = User::factory()->create();
        $this->actingAs($me);

        $notif = Notifikasi::create([
            'user_id' => $me->id,
            'judul' => 'Judul',
            'pesan' => 'isi',
            'url' => '/dashboard',
            'dibaca' => false,
        ]);

        Livewire::test(NotifikasiComponent::class)
            ->call('bukaNotifikasi', $notif->id)
            ->assertRedirect('/dashboard');

        $this->assertTrue($notif->fresh()->dibaca);
    }

    public function test_buka_notifikasi_does_nothing_when_url_is_null(): void
    {
        $me = User::factory()->create();
        $this->actingAs($me);

        $notif = Notifikasi::create(['user_id' => $me->id, 'judul' => 'Judul', 'pesan' => 'isi', 'dibaca' => false]);

        Livewire::test(NotifikasiComponent::class)
            ->call('bukaNotifikasi', $notif->id)
            ->assertNoRedirect();

        $this->assertFalse($notif->fresh()->dibaca);
    }

    public function test_buka_notifikasi_does_nothing_for_other_users_notifikasi(): void
    {
        $me = User::factory()->create();
        $other = User::factory()->create();
        $this->actingAs($me);

        $notif = Notifikasi::create([
            'user_id' => $other->id,
            'judul' => 'Judul',
            'pesan' => 'isi',
            'url' => '/dashboard',
            'dibaca' => false,
        ]);

        Livewire::test(NotifikasiComponent::class)
            ->call('bukaNotifikasi', $notif->id)
            ->assertNoRedirect();

        $this->assertFalse($notif->fresh()->dibaca);
    }

    public function test_notifikasi_page_shows_lihat_button_only_when_url_present(): void
    {
        $me = User::factory()->create();
        $this->actingAs($me);

        Notifikasi::create(['user_id' => $me->id, 'judul' => 'Ada Link', 'pesan' => 'isi', 'url' => '/dashboard', 'dibaca' => false]);
        Notifikasi::create(['user_id' => $me->id, 'judul' => 'Tanpa Link', 'pesan' => 'isi', 'dibaca' => false]);

        Livewire::test(NotifikasiComponent::class)
            ->assertSeeHtml('wire:click="bukaNotifikasi('.Notifikasi::where('judul', 'Ada Link')->first()->id.')"')
            ->assertDontSeeHtml('wire:click="bukaNotifikasi('.Notifikasi::where('judul', 'Tanpa Link')->first()->id.')"');
    }
}
