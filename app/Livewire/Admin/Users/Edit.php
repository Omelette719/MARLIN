<?php

namespace App\Livewire\Admin\Users;

use App\Concerns\ProfileValidationRules;
use App\Enums\Kelamin;
use App\Enums\Role;
use App\Models\User;
use Flux\Flux;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Edit Akun')]
class Edit extends Component
{
    use ProfileValidationRules;

    public User $user;

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

    public bool $aktif = true;

    public string $password = '';

    public function mount(User $user): void
    {
        $this->user = $user;
        $this->name = $user->name;
        $this->nama_panggilan = (string) $user->nama_panggilan;
        $this->nip = $user->nip;
        $this->username = (string) $user->username;
        $this->role = $user->role->value;
        $this->tanggal_lahir = $user->tanggal_lahir?->format('Y-m-d') ?? '';
        $this->jenis_kelamin = $user->jenis_kelamin?->value ?? '';
        $this->bidang = (string) $user->bidang;
        $this->jabatan = (string) $user->jabatan;
        $this->no_telepon = (string) $user->no_telepon;
        $this->aktif = $user->aktif;
    }

    // Fields with format rules (regex/date-comparison/uniqueness) that are
    // worth catching before the admin has finished editing the whole form —
    // same reasoning as the koordinat warning on Buat/Edit Surat.
    private const LIVE_VALIDATED_FIELDS = ['name', 'nama_panggilan', 'nip', 'tanggal_lahir', 'no_telepon'];

    public function updated(string $property): void
    {
        if (in_array($property, self::LIVE_VALIDATED_FIELDS, true)) {
            $this->validateOnly($property, $this->formRules(), $this->formMessages());
        }
    }

    // Shared with updated()'s live per-field validation, so the rules a
    // field is held to are identical whether it's checked while typing or
    // at final submit — one definition, never two copies to drift apart.
    private function formRules(): array
    {
        return [
            'name' => $this->nameRules(),
            'nama_panggilan' => ['nullable', 'string', 'max:255', 'regex:/^[a-zA-Z\s]+$/'],
            'nip' => ['required', 'string', 'max:30', 'regex:/^[0-9]+$/', 'unique:users,nip,'.$this->user->id],
            'username' => 'nullable|string|max:255|unique:users,username,'.$this->user->id,
            'role' => 'required|in:admin,user',
            'tanggal_lahir' => 'nullable|date|before:today',
            'jenis_kelamin' => 'nullable|in:L,P',
            'bidang' => 'nullable|string|max:255',
            'jabatan' => 'nullable|string|max:255',
            'no_telepon' => ['nullable', 'string', 'max:30', 'regex:/^[0-9]+$/'],
            'aktif' => 'boolean',
            'password' => 'nullable|string|min:8',
        ];
    }

    private function formMessages(): array
    {
        return [
            ...$this->nameMessages(),
            'nama_panggilan.regex' => 'Nama panggilan hanya boleh berisi huruf dan spasi, tanpa angka atau simbol.',
            'nip.regex' => 'NIP hanya boleh berisi angka.',
            'tanggal_lahir.before' => 'Tanggal lahir tidak boleh di masa depan.',
            'no_telepon.regex' => 'Nomor telepon hanya boleh berisi angka.',
        ];
    }

    public function save(): void
    {
        $validated = $this->validate($this->formRules(), $this->formMessages());

        foreach (['nama_panggilan', 'username', 'tanggal_lahir', 'jenis_kelamin', 'bidang', 'jabatan', 'no_telepon'] as $field) {
            $validated[$field] = $validated[$field] !== '' ? $validated[$field] : null;
        }

        if (! empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $this->user->update($validated);

        Flux::toast(variant: 'success', text: 'Akun berhasil diperbarui.');

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
        return view('pages::admin.users.edit');
    }
}
