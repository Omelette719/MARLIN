<?php

namespace Tests\Feature\Settings;

use App\Livewire\Settings\Security as SecurityComponent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Fortify\Features;
use Livewire\Livewire;
use Tests\TestCase;

class SecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->skipUnlessFortifyHas(Features::twoFactorAuthentication());

        Features::twoFactorAuthentication([
            'confirm' => true,
            'confirmPassword' => true,
        ]);
    }

    public function test_security_settings_page_can_be_rendered(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->get(route('security.edit'))
            ->assertOk()
            ->assertSee('Autentikasi Dua Faktor')
            ->assertSee('Aktifkan 2FA');
    }

    public function test_security_settings_page_requires_password_confirmation_when_enabled(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get(route('security.edit'));

        $response->assertRedirect(route('password.confirm'));
    }

    public function test_security_settings_page_renders_without_two_factor_when_feature_is_disabled(): void
    {
        config(['fortify.features' => []]);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->get(route('security.edit'))
            ->assertOk()
            ->assertSee('Ubah Kata Sandi')
            ->assertDontSee('Autentikasi Dua Faktor');
    }

    public function test_two_factor_authentication_disabled_when_confirmation_abandoned_between_requests(): void
    {
        $user = User::factory()->create();

        $user->forceFill([
            'two_factor_secret' => encrypt('test-secret'),
            'two_factor_recovery_codes' => encrypt(json_encode(['code1', 'code2'])),
            'two_factor_confirmed_at' => null,
        ])->save();

        $this->actingAs($user);

        $component = Livewire::test(SecurityComponent::class);

        $component->assertSet('twoFactorEnabled', false);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
        ]);
    }

    public function test_password_can_be_updated(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('password'),
        ]);

        $this->actingAs($user);

        $response = Livewire::test(SecurityComponent::class)
            ->set('current_password', 'password')
            ->set('password', 'new-password')
            ->set('password_confirmation', 'new-password')
            ->call('updatePassword');

        $response->assertHasNoErrors();

        $this->assertTrue(Hash::check('new-password', $user->refresh()->password));
    }

    public function test_password_cannot_be_updated_to_the_same_current_password(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('password'),
        ]);

        $this->actingAs($user);

        $response = Livewire::test(SecurityComponent::class)
            ->set('current_password', 'password')
            ->set('password', 'password')
            ->set('password_confirmation', 'password')
            ->call('updatePassword');

        $response->assertHasErrors(['password']);

        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }

    public function test_password_can_be_changed_back_to_an_older_password(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('password-one'),
        ]);

        $this->actingAs($user);

        Livewire::test(SecurityComponent::class)
            ->set('current_password', 'password-one')
            ->set('password', 'password-two')
            ->set('password_confirmation', 'password-two')
            ->call('updatePassword')
            ->assertHasNoErrors();

        $this->assertTrue(Hash::check('password-two', $user->fresh()->password));

        $response = Livewire::test(SecurityComponent::class)
            ->set('current_password', 'password-two')
            ->set('password', 'password-one')
            ->set('password_confirmation', 'password-one')
            ->call('updatePassword');

        $response->assertHasNoErrors();

        $this->assertTrue(Hash::check('password-one', $user->fresh()->password));
    }

    public function test_correct_password_must_be_provided_to_update_password(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('password'),
        ]);

        $this->actingAs($user);

        $response = Livewire::test(SecurityComponent::class)
            ->set('current_password', 'wrong-password')
            ->set('password', 'new-password')
            ->set('password_confirmation', 'new-password')
            ->call('updatePassword');

        $response->assertHasErrors(['current_password']);
    }
}
