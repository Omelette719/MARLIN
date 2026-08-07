<?php

namespace Database\Factories;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // firstName()+lastName() instead of name(): the latter's en_US
        // format occasionally appends a suffix like "Jr." or "MD", which
        // fails the letters-only validation now enforced on User::name.
        $name = fake()->firstName().' '.fake()->lastName();

        return [
            'name' => $name,
            'nama_panggilan' => Str::of($name)->explode(' ')->first(),
            'nip' => fake()->unique()->numerify('##################'),
            'username' => Str::of($name)->slug('.')->append('.'.fake()->unique()->numerify('##'))->toString(),
            'role' => Role::User,
            'tanggal_lahir' => fake()->dateTimeBetween('-55 years', '-22 years'),
            'jenis_kelamin' => fake()->randomElement(['L', 'P']),
            'bidang' => fake()->randomElement([
                'Sekretariat',
                'Bidang Lalu Lintas',
                'Bidang Angkutan',
                'Bidang Sarana dan Prasarana',
                'Bidang Keselamatan',
            ]),
            'jabatan' => fake()->randomElement([
                'Staf',
                'Kepala Seksi',
                'Kepala Bidang',
                'Analis Lalu Lintas',
            ]),
            'no_telepon' => fake()->numerify('08##########'),
            'aktif' => true,
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ];
    }

    /**
     * Indicate that the user is an admin.
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => Role::Admin,
        ]);
    }

    /**
     * Indicate that the model has two-factor authentication configured.
     */
    public function withTwoFactor(): static
    {
        return $this->state(fn (array $attributes) => [
            'two_factor_secret' => encrypt('secret'),
            'two_factor_recovery_codes' => encrypt(json_encode(['recovery-code-1'])),
            'two_factor_confirmed_at' => now(),
        ]);
    }
}
