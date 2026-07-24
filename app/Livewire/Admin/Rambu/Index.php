<?php

namespace App\Livewire\Admin\Rambu;

use App\Models\JenisRambu;
use App\Models\Rambu;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Daftar Rambu')]
class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $jenis = '';

    #[Url]
    public string $search = '';

    public function updatedJenis(): void
    {
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function with(): array
    {
        return [
            'rambu' => Rambu::with('jenisRambu')
                ->when($this->jenis, fn ($q) => $q->where('jenis_rambu_id', $this->jenis))
                ->when($this->search, fn ($q) => $q->where(fn ($w) => $w
                    ->where('wilayah', 'like', "%{$this->search}%")
                    ->orWhere('lokasi', 'like', "%{$this->search}%")))
                ->orderBy('wilayah')
                ->paginate(15),
            'jenisOptions' => JenisRambu::orderBy('nama_jenis')->get(),
            'jenisAktif' => $this->jenis ? JenisRambu::find($this->jenis) : null,
            'isAdmin' => Auth::user()->isAdmin(),
        ];
    }

    public function render()
    {
        return view('pages::admin.rambu.index');
    }
}
