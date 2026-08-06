<?php

namespace App\Livewire\Admin\Spk;

use App\Enums\JenisPekerjaan;
use App\Enums\StatusSpk;
use App\Models\Spk;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Daftar Surat')]
class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $jenis = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedJenis(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        return view('pages::admin.spk.index');
    }

    public function with(): array
    {
        // Selesai/dibatalkan SPKs live in Riwayat SPK instead — this list stays
        // scoped to active work so it doesn't get cluttered with closed-out surat.
        $spk = Spk::query()
            ->where('status', StatusSpk::Aktif->value)
            ->when($this->search, fn ($query) => $query->where(fn ($q) => $q
                ->where('nomor_surat', 'like', "%{$this->search}%")
                ->orWhere('wilayah', 'like', "%{$this->search}%")))
            ->when($this->jenis, fn ($query) => $query->where('jenis_spk', $this->jenis))
            ->withCount('rambuPasang')
            ->with(['rambuPasang' => fn ($q) => $q->with('rambu.jenisRambu')])
            ->orderByDesc('prioritas')
            ->orderBy('deadline')
            ->paginate(9);

        $spk->getCollection()->transform(function (Spk $item) {
            $item->cover_photos = $item->rambuPasang->pluck('foto_survei')->filter()->unique()->values();

            return $item;
        });

        return [
            'spk' => $spk,
            'jenisOptions' => JenisPekerjaan::cases(),
        ];
    }
}
