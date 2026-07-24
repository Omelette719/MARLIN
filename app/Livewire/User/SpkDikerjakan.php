<?php

namespace App\Livewire\User;

use App\Enums\StatusRambuPasang;
use App\Enums\StatusSpk;
use App\Models\DikerjakanOleh;
use App\Models\Spk;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('SPK Sedang Dikerjakan')]
class SpkDikerjakan extends Component
{
    use WithPagination;

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
            ->whereIn('id', $joinedSpkIds)
            ->where('status', StatusSpk::Aktif)
            ->withCount('rambuPasang')
            ->with(['rambuPasang:id,rambu_spk_id,status,foto_survei'])
            ->orderByDesc('prioritas')
            ->orderBy('deadline')
            ->paginate(9);

        $spk->getCollection()->transform(function (Spk $item) {
            $statuses = $item->rambuPasang->pluck('status')->reject(fn ($s) => $s === StatusRambuPasang::Batal);

            $item->progress_status = collect(self::STATUS_PRIORITAS)
                ->first(fn ($p) => $statuses->contains($p)) ?? StatusRambuPasang::Selesai;

            $item->cover_photo = $item->rambuPasang->first(fn ($rp) => filled($rp->foto_survei))?->foto_survei;

            $item->siap_diajukan = $item->rambuPasang->isNotEmpty() && $statuses->every(
                fn ($s) => in_array($s, [StatusRambuPasang::Tertunda, StatusRambuPasang::MenungguValidasi], true)
            );

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
