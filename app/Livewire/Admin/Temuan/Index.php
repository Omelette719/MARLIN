<?php

namespace App\Livewire\Admin\Temuan;

use App\Enums\StatusTindakLanjut;
use App\Models\LaporanKondisi;
use Flux\Flux;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Temuan Lapangan')]
class Index extends Component
{
    public function tolak(int $id): void
    {
        $laporan = LaporanKondisi::findOrFail($id);
        $laporan->update(['status_tindak_lanjut' => StatusTindakLanjut::Ditolak]);

        Flux::toast(variant: 'success', text: 'Temuan ditandai sebagai ditolak.');
    }

    public function with(): array
    {
        return [
            'temuan' => LaporanKondisi::where('status_tindak_lanjut', StatusTindakLanjut::Baru)
                ->with(['rambu.jenisRambu', 'pelapor'])
                ->latest()
                ->get(),
        ];
    }

    public function render()
    {
        return view('pages::admin.temuan.index');
    }
}
