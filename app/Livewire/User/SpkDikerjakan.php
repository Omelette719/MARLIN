<?php

namespace App\Livewire\User;

use App\Enums\StatusRambuPasang;
use App\Enums\StatusSpk;
use App\Models\DikerjakanOleh;
use App\Models\Spk;
use App\Support\SpkProgressStatus;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('SPK Sedang Dikerjakan')]
class SpkDikerjakan extends Component
{
    use WithPagination;

    public function with(): array
    {
        $joinedSpkIds = DikerjakanOleh::where('by_user_id', Auth::id())->pluck('by_spk_id');

        $spk = Spk::query()
            ->whereIn('id', $joinedSpkIds)
            ->where('status', StatusSpk::Aktif)
            ->withCount('rambuPasang')
            ->with(['rambuPasang:id,rambu_spk_id,status,jenis_pekerjaan,foto_survei'])
            ->orderByDesc('prioritas')
            ->orderBy('deadline')
            ->paginate(9);

        $spk->getCollection()->transform(function (Spk $item) {
            $statuses = $item->rambuPasang->pluck('status')->reject(fn ($s) => $s === StatusRambuPasang::Batal);

            $item->progress_status = SpkProgressStatus::hitung($item, $statuses);
            $item->siap_diajukan = SpkProgressStatus::siapDiajukan($item, $statuses);

            $item->cover_photos = $item->rambuPasang->pluck('foto_survei')->filter()->unique()->values();

            return $item;
        });

        return [
            'spk' => $spk,
            'sayaPerwakilanIds' => DikerjakanOleh::where('by_user_id', Auth::id())
                ->where('is_perwakilan', true)
                ->pluck('by_spk_id'),
        ];
    }

    public function render()
    {
        return view('pages::user.spk-dikerjakan');
    }
}
