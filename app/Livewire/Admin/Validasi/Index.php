<?php

namespace App\Livewire\Admin\Validasi;

use App\Enums\StatusRambuPasang;
use App\Models\RambuPasang;
use App\Models\Spk;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Validasi Laporan')]
class Index extends Component
{
    public function with(): array
    {
        $spk = Spk::whereNotNull('laporan_akhir_diajukan_at')
            ->orderBy('laporan_akhir_diajukan_at')
            ->get()
            ->map(function (Spk $item) {
                $item->selesai_count = RambuPasang::where('rambu_spk_id', $item->id)
                    ->where('status', StatusRambuPasang::MenungguValidasi->value)
                    ->count();

                $item->terkendala_count = RambuPasang::where('rambu_spk_id', $item->id)
                    ->where('status', StatusRambuPasang::Tertunda->value)
                    ->count();

                return $item;
            });

        return [
            'spk' => $spk,
        ];
    }

    public function render()
    {
        return view('pages::admin.validasi.index');
    }
}
