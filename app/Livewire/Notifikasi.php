<?php

namespace App\Livewire;

use App\Models\Notifikasi as NotifikasiModel;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Notifikasi')]
class Notifikasi extends Component
{
    use WithPagination;

    public function tandaiDibaca(int $id): void
    {
        NotifikasiModel::where('id', $id)
            ->where('user_id', Auth::id())
            ->update(['dibaca' => true]);
    }

    public function tandaiSemuaDibaca(): void
    {
        NotifikasiModel::where('user_id', Auth::id())
            ->where('dibaca', false)
            ->update(['dibaca' => true]);
    }

    public function with(): array
    {
        return [
            'notifikasi' => NotifikasiModel::where('user_id', Auth::id())
                ->latest()
                ->paginate(15),
        ];
    }

    public function render()
    {
        return view('pages::notifikasi');
    }
}
