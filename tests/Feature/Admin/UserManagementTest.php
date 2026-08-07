<?php

namespace Tests\Feature\Admin;

use App\Enums\Role;
use App\Livewire\Admin\Users\Create as UsersCreateComponent;
use App\Livewire\Admin\Users\Edit as UsersEditComponent;
use App\Livewire\Admin\Users\Index as UsersIndexComponent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_petugas_cannot_access_user_management(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(route('admin.users.index'))->assertRedirect(route('dashboard'));
    }

    public function test_admin_can_create_petugas_account(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(UsersCreateComponent::class)
            ->set('name', 'Petugas Baru')
            ->set('nip', '199901012020011001')
            ->set('role', 'user')
            ->set('password', 'password123')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('admin.users.index'));

        $user = User::where('nip', '199901012020011001')->first();
        $this->assertNotNull($user);
        $this->assertSame(Role::User, $user->role);
        $this->assertTrue($user->aktif);
        $this->assertTrue(Hash::check('password123', $user->password));
    }

    public function test_nip_with_letters_is_rejected_on_create(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(UsersCreateComponent::class)
            ->set('name', 'Petugas Baru')
            ->set('nip', '1999BOGUS2020')
            ->set('role', 'user')
            ->set('password', 'password123')
            ->call('save')
            ->assertHasErrors(['nip' => 'regex']);

        $this->assertSame(0, User::where('name', 'Petugas Baru')->count());
    }

    public function test_no_telepon_with_letters_is_rejected_on_create(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(UsersCreateComponent::class)
            ->set('name', 'Petugas Baru')
            ->set('nip', '199901012020011002')
            ->set('role', 'user')
            ->set('no_telepon', 'bukan nomor telepon')
            ->set('password', 'password123')
            ->call('save')
            ->assertHasErrors(['no_telepon' => 'regex']);

        $this->assertSame(0, User::where('name', 'Petugas Baru')->count());
    }

    public function test_no_telepon_with_symbols_is_rejected_on_create(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(UsersCreateComponent::class)
            ->set('name', 'Petugas Baru')
            ->set('nip', '199901012020011010')
            ->set('role', 'user')
            ->set('no_telepon', '0812-345-6789')
            ->set('password', 'password123')
            ->call('save')
            ->assertHasErrors(['no_telepon' => 'regex']);

        $this->assertSame(0, User::where('name', 'Petugas Baru')->count());
    }

    public function test_name_with_numbers_or_symbols_is_rejected_on_create(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(UsersCreateComponent::class)
            ->set('name', 'Petugas 2 Baru!')
            ->set('nip', '199901012020011011')
            ->set('role', 'user')
            ->set('password', 'password123')
            ->call('save')
            ->assertHasErrors(['name' => 'regex']);

        $this->assertSame(0, User::where('nip', '199901012020011011')->count());
    }

    public function test_tanggal_lahir_in_future_is_rejected_on_create(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(UsersCreateComponent::class)
            ->set('name', 'Petugas Baru')
            ->set('nip', '199901012020011003')
            ->set('role', 'user')
            ->set('tanggal_lahir', now()->addYear()->toDateString())
            ->set('password', 'password123')
            ->call('save')
            ->assertHasErrors(['tanggal_lahir' => 'before']);

        $this->assertSame(0, User::where('name', 'Petugas Baru')->count());
    }

    public function test_nip_with_letters_is_rejected_on_edit(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $user = User::factory()->create(['nip' => '199901012020011004']);

        Livewire::test(UsersEditComponent::class, ['user' => $user])
            ->set('nip', 'ABC123')
            ->call('save')
            ->assertHasErrors(['nip' => 'regex']);

        $this->assertSame('199901012020011004', $user->fresh()->nip);
    }

    public function test_name_with_numbers_or_symbols_is_rejected_on_edit(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $user = User::factory()->create(['name' => 'Nama Asli']);

        Livewire::test(UsersEditComponent::class, ['user' => $user])
            ->set('name', 'Nama-Palsu99')
            ->call('save')
            ->assertHasErrors(['name' => 'regex']);

        $this->assertSame('Nama Asli', $user->fresh()->name);
    }

    public function test_no_telepon_with_symbols_is_rejected_on_edit(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $user = User::factory()->create(['no_telepon' => '081234567890']);

        Livewire::test(UsersEditComponent::class, ['user' => $user])
            ->set('no_telepon', '(0812) 3456-789')
            ->call('save')
            ->assertHasErrors(['no_telepon' => 'regex']);

        $this->assertSame('081234567890', $user->fresh()->no_telepon);
    }

    public function test_admin_can_edit_user_without_changing_password(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $user = User::factory()->create(['jabatan' => 'Staf']);
        $originalPassword = $user->password;

        Livewire::test(UsersEditComponent::class, ['user' => $user])
            ->set('jabatan', 'Kepala Seksi')
            ->call('save')
            ->assertHasNoErrors();

        $user->refresh();
        $this->assertSame('Kepala Seksi', $user->jabatan);
        $this->assertSame($originalPassword, $user->password);
    }

    public function test_admin_can_deactivate_and_reactivate_petugas(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $user = User::factory()->create(['aktif' => true]);

        Livewire::test(UsersIndexComponent::class)->call('toggleAktif', $user->id);
        $this->assertFalse($user->fresh()->aktif);

        Livewire::test(UsersIndexComponent::class)->call('toggleAktif', $user->id);
        $this->assertTrue($user->fresh()->aktif);
    }

    public function test_admin_cannot_deactivate_own_account(): void
    {
        $admin = User::factory()->admin()->create(['aktif' => true]);
        $this->actingAs($admin);

        Livewire::test(UsersIndexComponent::class)->call('toggleAktif', $admin->id);

        $this->assertTrue($admin->fresh()->aktif);
    }
}
