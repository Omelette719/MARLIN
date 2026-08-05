<?php

namespace App\Livewire\Admin\Users;

use App\Enums\Kelamin;
use App\Enums\Role;
use App\Models\User;
use Flux\Flux;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Tambah Akun')]
class Create extends Component
{
    public string $name = '';

    public string $nama_panggilan = '';

    public string $nip = '';

    public string $username = '';

    public string $role = 'user';

    public string $tanggal_lahir = '';

    public string $jenis_kelamin = '';

    public string $bidang = '';

    public string $jabatan = '';

    public string $no_telepon = '';

    public string $password = '';

    public function save(): void
    {
        $validated = $this->validate([
            'name' => 'required|string|max:255',
            'nama_panggilan' => 'nullable|string|max:255',
            'nip' => ['required', 'string', 'max:30', 'regex:/^[0-9]+$/', 'unique:users,nip'],
            'username' => 'nullable|string|max:255|unique:users,username',
            'role' => 'required|in:admin,user',
            'tanggal_lahir' => 'nullable|date|before:today',
            'jenis_kelamin' => 'nullable|in:L,P',
            'bidang' => 'nullable|string|max:255',
            'jabatan' => 'nullable|string|max:255',
            'no_telepon' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+\-\s()]+$/'],
            'password' => 'required|string|min:8',
        ], [
            'nip.regex' => 'NIP hanya boleh berisi angka.',
            'tanggal_lahir.before' => 'Tanggal lahir tidak boleh di masa depan.',
            'no_telepon.regex' => 'Nomor telepon hanya boleh berisi angka dan karakter +, -, spasi, atau tanda kurung.',
        ]);

        foreach (['nama_panggilan', 'username', 'tanggal_lahir', 'jenis_kelamin', 'bidang', 'jabatan', 'no_telepon'] as $field) {
            $validated[$field] = $validated[$field] !== '' ? $validated[$field] : null;
        }

        $validated['password'] = Hash::make($validated['password']);
        $validated['aktif'] = true;

        User::create($validated);

        Flux::toast(variant: 'success', text: 'Akun berhasil dibuat.');

        $this->redirectRoute('admin.users.index', navigate: true);
    }

    public function with(): array
    {
        return [
            'roleOptions' => Role::cases(),
            'kelaminOptions' => Kelamin::cases(),
        ];
    }

    public function render()
    {
        return view('pages::admin.users.create');
    }
}
