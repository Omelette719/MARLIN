<?php

namespace App\Livewire\Admin;

use App\Enums\StatusLaporan;
use App\Enums\StatusSpk;
use App\Models\LaporanKondisi;
use App\Models\LaporanPengerjaan;
use App\Models\Rambu;
use App\Models\Spk;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Dashboard')]
class Dashboard extends Component
{
    public function with(): array
    {
        $rambuTerpasang = Rambu::where('sudah_terpasang', true)->count();
        $rambuBelumTerpasang = Rambu::where('sudah_terpasang', false)->count();
        $totalRambu = $rambuTerpasang + $rambuBelumTerpasang;

        $wilayahBreakdown = Rambu::selectRaw('wilayah, count(*) as total, sum(case when sudah_terpasang then 1 else 0 end) as terpasang')
            ->groupBy('wilayah')
            ->orderByDesc('total')
            ->get();

        return [
            'rambuTerpasang' => $rambuTerpasang,
            'rambuBelumTerpasang' => $rambuBelumTerpasang,
            'totalRambu' => $totalRambu,
            'spkAktifCount' => Spk::where('status', StatusSpk::Aktif)->count(),
            'menungguValidasiCount' => LaporanPengerjaan::where('status', StatusLaporan::Diajukan)->count(),
            'temuanBaruCount' => LaporanKondisi::where('status_tindak_lanjut', 'baru')->count(),
            'progresPersen' => $totalRambu > 0 ? round(($rambuTerpasang / $totalRambu) * 100) : 0,
            'wilayahBreakdown' => $wilayahBreakdown,
            'spkTerbaru' => Spk::withCount('rambuPasang')
                ->latest()
                ->limit(6)
                ->get()
                ->map(function (Spk $spk) {
                    $selesai = $spk->rambuPasang()->where('status', 'selesai')->count();

                    return [
                        'spk' => $spk,
                        'selesai' => $selesai,
                        'total' => $spk->rambu_pasang_count,
                    ];
                }),
        ];
    }

    public function render()
    {
        return view('pages::admin.dashboard');
    }
}
