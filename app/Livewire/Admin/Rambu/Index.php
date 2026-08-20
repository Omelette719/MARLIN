<?php

namespace App\Livewire\Admin\Rambu;

use App\Enums\KondisiRambu;
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

    #[Url]
    public string $kondisi = '';

    #[Url]
    public string $status = '';

    public function updatedJenis(): void
    {
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedKondisi(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
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
                // Kondisi only has meaning once a sign is actually installed
                // (the table shows "N/A" for anything not sudah_terpasang,
                // even though the column defaults to "baik" in the DB), so
                // filtering by Baik/Rusak must also require sudah_terpasang
                // to match what's actually shown.
                ->when($this->kondisi, fn ($q) => $q->where('sudah_terpasang', true)->where('kondisi_terkini', $this->kondisi))
                ->when($this->status === 'terpasang', fn ($q) => $q->where('sudah_terpasang', true))
                ->when($this->status === 'belum_terpasang', fn ($q) => $q->where('sudah_terpasang', false))
                ->orderBy('wilayah')
                ->paginate(15),
            'jenisOptions' => JenisRambu::orderBy('nama_jenis')->get(),
            'jenisAktif' => $this->jenis ? JenisRambu::find($this->jenis) : null,
            'kondisiOptions' => KondisiRambu::cases(),
            'isAdmin' => Auth::user()->isAdmin(),
        ];
    }

    public function render()
    {
        return view('pages::admin.rambu.index');
    }
}
