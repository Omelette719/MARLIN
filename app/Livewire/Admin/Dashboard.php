<?php

namespace App\Livewire\Admin;

use App\Enums\StatusLaporan;
use App\Enums\StatusSpk;
use App\Enums\Urgensi;
use App\Models\LaporanKondisi;
use App\Models\LaporanPengerjaan;
use App\Models\Rambu;
use App\Models\Spk;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Dashboard')]
class Dashboard extends Component
{
    // SPKs that need attention first (prioritas/urgensi tinggi), then the
    // rest ordered by least progress first — so the admin sees what's both
    // urgent and what's furthest behind, not just whatever was created last.
    private function spkPrioritas()
    {
        return Spk::where('status', StatusSpk::Aktif)
            ->withCount('rambuPasang')
            ->with(['rambuPasang' => fn ($q) => $q->select('id', 'rambu_spk_id', 'status')])
            ->get()
            ->map(function (Spk $spk) {
                $rambuAktif = $spk->rambuPasang->where('status', '!=', 'batal');
                $total = $rambuAktif->count();
                $selesai = $rambuAktif->where('status', 'selesai')->count();

                return [
                    'spk' => $spk,
                    'selesai' => $selesai,
                    'total' => $total,
                    'progres' => $total > 0 ? $selesai / $total : 1,
                    'butuhPerhatian' => $spk->prioritas || $spk->urgensi === Urgensi::Tinggi,
                ];
            })
            ->sort(fn ($a, $b) => match (true) {
                $a['butuhPerhatian'] !== $b['butuhPerhatian'] => $a['butuhPerhatian'] ? -1 : 1,
                $a['butuhPerhatian'] => $a['spk']->deadline <=> $b['spk']->deadline,
                default => $a['progres'] <=> $b['progres'] ?: $a['spk']->deadline <=> $b['spk']->deadline,
            })
            ->take(5)
            ->values();
    }

    public function with(): array
    {
        $rambuTerpasang = Rambu::where('sudah_terpasang', true)->count();
        $rambuBelumTerpasang = Rambu::where('sudah_terpasang', false)->count();
        $totalRambu = $rambuTerpasang + $rambuBelumTerpasang;

        return [
            'rambuTerpasang' => $rambuTerpasang,
            'rambuBelumTerpasang' => $rambuBelumTerpasang,
            'totalRambu' => $totalRambu,
            'spkAktifCount' => Spk::where('status', StatusSpk::Aktif)->count(),
            'menungguValidasiCount' => LaporanPengerjaan::where('status', StatusLaporan::Diajukan)->count(),
            'temuanBaruCount' => LaporanKondisi::where('status_tindak_lanjut', 'baru')->count(),
            'progresPersen' => $totalRambu > 0 ? round(($rambuTerpasang / $totalRambu) * 100) : 0,
            'spkPrioritas' => $this->spkPrioritas(),
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
