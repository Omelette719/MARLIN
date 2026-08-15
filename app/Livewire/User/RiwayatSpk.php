<?php

namespace App\Livewire\User;

use App\Models\DikerjakanOleh;
use App\Models\Spk;
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
    public string $tanggal_dari = '';

    #[Url]
    public string $tanggal_sampai = '';

    public function updatedTanggalDari(): void
    {
        $this->resetPage();
    }

    public function updatedTanggalSampai(): void
    {
        $this->resetPage();
    }

    public function with(): array
    {
        // "Riwayat" is anchored on when the petugas was assigned to the SPK
        // (dikerjakan_oleh.created_at) — that's the most faithful proxy for
        // "this is the work I was doing during this period," regardless of
        // whether the SPK itself finished later. No filter selected means
        // the full history, not just the current month.
        $spkIds = DikerjakanOleh::where('by_user_id', Auth::id())
            ->when($this->tanggal_dari, fn ($query) => $query->whereDate('created_at', '>=', $this->tanggal_dari))
            ->when($this->tanggal_sampai, fn ($query) => $query->whereDate('created_at', '<=', $this->tanggal_sampai))
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
        ];
    }

    public function render()
    {
        return view('pages::user.riwayat-spk');
    }
}
