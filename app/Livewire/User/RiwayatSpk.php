<?php

namespace App\Livewire\User;

use App\Models\DikerjakanOleh;
use App\Models\Spk;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Riwayat Pekerjaan Saya')]
class RiwayatSpk extends Component
{
    use WithPagination;

    #[Url]
    public string $bulan = '';

    public function mount(): void
    {
        if (! $this->bulan) {
            $this->bulan = now()->format('Y-m');
        }
    }

    public function updatedBulan(): void
    {
        $this->resetPage();
    }

    public function with(): array
    {
        $awal = Carbon::createFromFormat('Y-m', $this->bulan)->startOfMonth();
        $akhir = $awal->copy()->endOfMonth();

        // "Riwayat" is anchored on when the petugas was assigned to the SPK
        // (dikerjakan_oleh.created_at) — that's the most faithful proxy for
        // "this is the work I was doing during this month," regardless of
        // whether the SPK itself finished later.
        $spkIds = DikerjakanOleh::where('by_user_id', Auth::id())
            ->whereBetween('created_at', [$awal, $akhir])
            ->pluck('by_spk_id');

        $spk = Spk::query()
            ->whereIn('id', $spkIds)
            ->withCount('rambuPasang')
            ->with(['rambuPasang:id,rambu_spk_id,status,jenis_pekerjaan,foto_survei'])
            ->orderByDesc('deadline')
            ->paginate(9);

        $spk->getCollection()->transform(function (Spk $item) {
            $item->cover_photos = $item->rambuPasang->pluck('foto_survei')->filter()->unique()->values();
            $item->selesai_count = $item->rambuPasang->where('status', 'selesai')->count();

            return $item;
        });

        return [
            'spk' => $spk,
            'periodeLabel' => $awal->translatedFormat('F Y'),
        ];
    }

    public function render()
    {
        return view('pages::user.riwayat-spk');
    }
}
