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
    public string $status = '';

    #[Url]
    public string $jenis = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
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
        $spk = Spk::query()
            ->when($this->search, fn ($query) => $query->where(fn ($q) => $q
                ->where('nomor_surat', 'like', "%{$this->search}%")
                ->orWhere('wilayah', 'like', "%{$this->search}%")))
            ->when(
                $this->status,
                fn ($query) => $query->where('status', $this->status),
                // "Selesai" SPKs are effectively archived — hidden from the default
                // view, but still reachable by explicitly picking that status filter.
                fn ($query) => $query->where('status', '!=', StatusSpk::Selesai->value)
            )
            ->when($this->jenis, fn ($query) => $query->where('jenis_spk', $this->jenis))
            ->withCount('rambuPasang')
            ->with(['rambuPasang' => fn ($q) => $q->with('rambu.jenisRambu')])
            ->orderByDesc('prioritas')
            ->orderBy('deadline')
            ->paginate(9);

        $spk->getCollection()->transform(function (Spk $item) {
            $dengan_foto = $item->rambuPasang->first(fn ($rp) => filled($rp->foto_survei));
            $item->cover_photo = $dengan_foto?->foto_survei;

            return $item;
        });

        return [
            'spk' => $spk,
            'statuses' => StatusSpk::cases(),
            'jenisOptions' => JenisPekerjaan::cases(),
        ];
    }
}
