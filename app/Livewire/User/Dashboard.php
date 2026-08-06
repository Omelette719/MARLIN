<?php

namespace App\Livewire\User;

use App\Enums\StatusRambuPasang;
use App\Enums\StatusSpk;
use App\Models\DikerjakanOleh;
use App\Models\RambuPasang;
use App\Models\Spk;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Daftar Surat Aktif')]
class Dashboard extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    private const STATUS_PRIORITAS = [
        StatusRambuPasang::Urgent,
        StatusRambuPasang::Tertunda,
        StatusRambuPasang::Revisi,
        StatusRambuPasang::MenungguValidasi,
        StatusRambuPasang::Belum,
        StatusRambuPasang::Selesai,
    ];

    public function with(): array
    {
        $joinedSpkIds = DikerjakanOleh::where('by_user_id', Auth::id())->pluck('by_spk_id');

        $spk = Spk::query()
            ->where('status', StatusSpk::Aktif)
            ->when($this->search, fn ($query) => $query->where(fn ($q) => $q
                ->where('nomor_surat', 'like', "%{$this->search}%")
                ->orWhere('wilayah', 'like', "%{$this->search}%")))
            ->withCount('rambuPasang')
            ->with(['rambuPasang:id,rambu_spk_id,status,foto_survei'])
            ->orderByDesc('prioritas')
            ->orderBy('deadline')
            ->paginate(9);

        $spk->getCollection()->transform(function (Spk $item) {
            $statuses = $item->rambuPasang->pluck('status')->reject(fn ($s) => $s === StatusRambuPasang::Batal);

            $item->progress_status = collect(self::STATUS_PRIORITAS)
                ->first(fn ($p) => $statuses->contains($p)) ?? StatusRambuPasang::Selesai;

            $item->cover_photos = $item->rambuPasang->pluck('foto_survei')->filter()->unique()->values();

            return $item;
        });

        $joinedRambuPasang = RambuPasang::whereIn('rambu_spk_id', $joinedSpkIds);

        return [
            'spk' => $spk,
            'joinedSpkIds' => $joinedSpkIds,
            'aktifCount' => (clone $joinedRambuPasang)->whereIn('status', [
                StatusRambuPasang::Belum->value, StatusRambuPasang::Revisi->value, StatusRambuPasang::Tertunda->value,
            ])->count(),
            'progresCount' => (clone $joinedRambuPasang)->where('status', StatusRambuPasang::MenungguValidasi->value)->count(),
            'mendekatiDeadlineCount' => Spk::whereIn('id', $joinedSpkIds)
                ->where('status', StatusSpk::Aktif)
                ->whereBetween('deadline', [now()->startOfDay(), now()->addDays(3)->endOfDay()])
                ->count(),
            'selesaiBulanIniCount' => (clone $joinedRambuPasang)
                ->where('status', StatusRambuPasang::Selesai->value)
                ->whereBetween('updated_at', [now()->startOfMonth(), now()->endOfMonth()])
                ->count(),
        ];
    }

    public function render()
    {
        return view('pages::user.dashboard');
    }
}
