<?php

namespace App\Livewire\Admin\Users;

use App\Models\User;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Manajemen Petugas')]
class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function toggleAktif(int $id): void
    {
        $user = User::findOrFail($id);

        if ($user->id === Auth::id()) {
            Flux::toast(variant: 'danger', text: 'Kamu tidak bisa menonaktifkan akunmu sendiri.');

            return;
        }

        $user->update(['aktif' => ! $user->aktif]);

        Flux::toast(variant: 'success', text: $user->aktif ? 'Akun diaktifkan.' : 'Akun dinonaktifkan.');
    }

    public function with(): array
    {
        return [
            'users' => User::query()
                ->when($this->search, fn ($q) => $q->where(fn ($w) => $w
                    ->where('name', 'like', "%{$this->search}%")
                    ->orWhere('nip', 'like', "%{$this->search}%")))
                ->orderBy('name')
                ->paginate(15),
        ];
    }

    public function render()
    {
        return view('pages::admin.users.index');
    }
}
