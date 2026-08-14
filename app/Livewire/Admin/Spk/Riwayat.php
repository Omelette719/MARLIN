<?php

namespace App\Livewire\Admin\Spk;

use App\Enums\JenisPekerjaan;
use App\Enums\StatusSpk;
use App\Models\Spk;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Riwayat SPK')]
class Riwayat extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $status = '';

    #[Url]
    public string $jenis = '';

    #[Url]
    public string $tanggal_dari = '';

    #[Url]
    public string $tanggal_sampai = '';

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

    public function updatedTanggalDari(): void
    {
        $this->resetPage();
    }

    public function updatedTanggalSampai(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        return view('pages::admin.spk.riwayat');
    }

    public function with(): array
    {
        $spk = Spk::query()
            ->whereIn('status', [StatusSpk::Selesai->value, StatusSpk::Dibatalkan->value])
            ->when($this->search, fn ($query) => $query->where(fn ($q) => $q
                ->where('nomor_surat', 'like', "%{$this->search}%")
                ->orWhere('wilayah', 'like', "%{$this->search}%")))
            ->when($this->status, fn ($query) => $query->where('status', $this->status))
            ->when($this->jenis === 'campuran', fn ($query) => $query
                ->whereHas('rambuPasang', fn ($q) => $q->where('jenis_pekerjaan', 'pasang_baru'))
                ->whereHas('rambuPasang', fn ($q) => $q->where('jenis_pekerjaan', 'perbaikan')))
            ->when($this->jenis && $this->jenis !== 'campuran', fn ($query) => $query->whereHas('rambuPasang', fn ($q) => $q->where('jenis_pekerjaan', $this->jenis)))
            ->when($this->tanggal_dari, fn ($query) => $query->whereDate('updated_at', '>=', $this->tanggal_dari))
            ->when($this->tanggal_sampai, fn ($query) => $query->whereDate('updated_at', '<=', $this->tanggal_sampai))
            ->withCount(['rambuPasang', 'dikerjakanOleh'])
            ->with(['rambuPasang' => fn ($q) => $q->with('rambu.jenisRambu')])
            ->orderByDesc('updated_at')
            ->paginate(9);

        $spk->getCollection()->transform(function (Spk $item) {
            $item->cover_photos = $item->rambuPasang->pluck('foto_survei')->filter()->unique()->values();

            return $item;
        });

        return [
            'spk' => $spk,
            'statuses' => [StatusSpk::Selesai, StatusSpk::Dibatalkan],
            'jenisOptions' => JenisPekerjaan::cases(),
        ];
    }
}
