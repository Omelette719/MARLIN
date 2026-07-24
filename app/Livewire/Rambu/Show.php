<?php

namespace App\Livewire\Rambu;

use App\Models\Rambu;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Detail Rambu')]
class Show extends Component
{
    public Rambu $rambu;

    public function mount(Rambu $rambu): void
    {
        $this->rambu = $rambu->load([
            'jenisRambu',
            'rambuPasang' => fn ($q) => $q->latest()->with(['spk', 'laporanPengerjaan']),
        ]);
    }

    public function with(): array
    {
        return [
            'isAdmin' => Auth::user()->isAdmin(),
            'foto' => $this->rambu->fotoUtama(),
            'riwayat' => $this->rambu->rambuPasang,
            'googleMapsUrl' => $this->rambu->koordinat && str_contains($this->rambu->koordinat, ',')
                ? 'https://www.google.com/maps/search/?api=1&query='.$this->rambu->koordinat
                : null,
        ];
    }

    public function render()
    {
        return view('pages::rambu.show');
    }
}
