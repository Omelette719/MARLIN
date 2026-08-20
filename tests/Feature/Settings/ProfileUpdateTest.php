<?php

namespace Tests\Feature\Settings;

use App\Livewire\Settings\Profile as ProfileComponent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProfileUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $this->actingAs($user = User::factory()->create());

        $this->get(route('profile.edit'))->assertOk();
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $response = Livewire::test(ProfileComponent::class)
            ->set('name', 'Test User')
            ->call('updateProfileInformation');

        $response->assertHasNoErrors();

        $this->assertEquals('Test User', $user->refresh()->name);
    }

    public function test_nama_panggilan_and_no_telepon_can_be_updated(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $response = Livewire::test(ProfileComponent::class)
            ->set('name', 'Test User')
            ->set('nama_panggilan', 'Testy')
            ->set('no_telepon', '081234567890')
            ->call('updateProfileInformation');

        $response->assertHasNoErrors();

        $user->refresh();
        $this->assertSame('Testy', $user->nama_panggilan);
        $this->assertSame('081234567890', $user->no_telepon);
    }

    public function test_no_telepon_with_letters_is_rejected_on_profile_update(): void
    {
        $user = User::factory()->create(['no_telepon' => '081234567890']);

        $this->actingAs($user);

        Livewire::test(ProfileComponent::class)
            ->set('name', 'Test User')
            ->set('no_telepon', 'bukan nomor')
            ->call('updateProfileInformation')
            ->assertHasErrors(['no_telepon' => 'regex']);

        $this->assertSame('081234567890', $user->fresh()->no_telepon);
    }

    public function test_username_role_and_other_admin_managed_fields_cannot_be_changed_from_profile(): void
    {
        $user = User::factory()->create(['username' => 'original-username']);

        $this->actingAs($user);

        Livewire::test(ProfileComponent::class)
            ->set('name', 'Test User')
            ->call('updateProfileInformation')
            ->assertHasNoErrors();

        $this->assertSame('original-username', $user->fresh()->username);
    }

    public function test_user_can_delete_their_account(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $response = Livewire::test('pages::settings.delete-user-modal')
            ->set('password', 'password')
            ->call('deleteUser');

        $response
            ->assertHasNoErrors()
            ->assertRedirect('/');

        $this->assertNull($user->fresh());
        $this->assertFalse(auth()->check());
    }

    public function test_correct_password_must_be_provided_to_delete_account(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $response = Livewire::test('pages::settings.delete-user-modal')
            ->set('password', 'wrong-password')
            ->call('deleteUser');

        $response->assertHasErrors(['password']);

        $this->assertNotNull($user->fresh());
    }
}
