<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Fortify\Features;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get(route('login'));

        $response->assertOk();
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create();

        $response = $this->post(route('login.store'), [
            'nip' => $user->nip,
            'password' => 'password',
        ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticated();
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $response = $this->post(route('login.store'), [
            'nip' => $user->nip,
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrorsIn('email');

        $this->assertGuest();
    }

    public function test_users_with_two_factor_enabled_are_redirected_to_two_factor_challenge(): void
    {
        $this->skipUnlessFortifyHas(Features::twoFactorAuthentication());

        Features::twoFactorAuthentication([
            'confirm' => true,
            'confirmPassword' => true,
        ]);

        $user = User::factory()->withTwoFactor()->create();

        $response = $this->post(route('login.store'), [
            'nip' => $user->nip,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('two-factor.login'));
        $this->assertGuest();
    }

    public function test_deactivated_users_cannot_authenticate(): void
    {
        $user = User::factory()->create(['aktif' => false]);

        $response = $this->post(route('login.store'), [
            'nip' => $user->nip,
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors('nip');
        $this->assertGuest();
    }

    public function test_account_is_locked_out_after_six_consecutive_failed_attempts(): void
    {
        $user = User::factory()->create(['aktif' => true]);

        for ($i = 0; $i < 5; $i++) {
            $this->post(route('login.store'), [
                'nip' => $user->nip,
                'password' => 'wrong-password',
            ]);
        }

        $this->assertTrue($user->fresh()->aktif);

        $response = $this->post(route('login.store'), [
            'nip' => $user->nip,
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors('nip');
        $this->assertFalse($user->fresh()->aktif);
        $this->assertSame(6, $user->fresh()->failed_login_attempts);
    }

    public function test_failed_login_attempts_reset_after_a_successful_login(): void
    {
        $user = User::factory()->create(['aktif' => true]);

        for ($i = 0; $i < 3; $i++) {
            $this->post(route('login.store'), [
                'nip' => $user->nip,
                'password' => 'wrong-password',
            ]);
        }

        $this->assertSame(3, $user->fresh()->failed_login_attempts);

        $this->post(route('login.store'), [
            'nip' => $user->nip,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $this->assertSame(0, $user->fresh()->failed_login_attempts);
    }

    public function test_users_can_logout(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('logout'));

        $response->assertRedirect(route('home'));

        $this->assertGuest();
    }
}
