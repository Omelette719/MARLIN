<?php

namespace App\Support;

use App\Enums\StatusSpk;
use App\Models\Kendala;
use App\Models\LaporanKondisi;
use App\Models\Rambu;
use App\Models\Spk;
use Carbon\Carbon;

class LaporanBulanan
{
    /**
     * Build the monthly work-history report data for a given "Y-m" period.
     * Shared by the admin preview page and the PDF export so both always
     * show identical numbers.
     */
    public static function build(string $bulan): array
    {
        $awal = Carbon::createFromFormat('Y-m', $bulan)->startOfMonth();
        $akhir = $awal->copy()->endOfMonth();

        $rambuTotal = Rambu::count();
        $rambuTerpasang = Rambu::where('sudah_terpasang', true)->count();

        $spkDibuatBulanIni = Spk::whereBetween('created_at', [$awal, $akhir])->get();

        $spkSelesaiBulanIni = Spk::where('status', StatusSpk::Selesai)
            ->whereBetween('updated_at', [$awal, $akhir])
            ->withCount('rambuPasang')
            ->orderBy('nomor_surat')
            ->get();

        $spkAktif = Spk::where('status', StatusSpk::Aktif)
            ->where('created_at', '<=', $akhir)
            ->withCount('rambuPasang')
            ->with(['rambuPasang' => fn ($q) => $q->select('id', 'rambu_spk_id', 'status')])
            ->orderBy('deadline')
            ->get()
            ->each(function (Spk $spk) {
                $spk->selesai_count = $spk->rambuPasang->where('status', 'selesai')->count();
            });

        return [
            'bulan' => $bulan,
            'periodeLabel' => $awal->translatedFormat('F Y'),
            'awal' => $awal,
            'akhir' => $akhir,
            'rambu' => [
                'total' => $rambuTotal,
                'terpasang' => $rambuTerpasang,
                'belum_terpasang' => $rambuTotal - $rambuTerpasang,
                'kondisi_baik' => Rambu::where('kondisi_terkini', 'baik')->count(),
                'kondisi_rusak' => Rambu::where('kondisi_terkini', 'rusak')->count(),
            ],
            'spk' => [
                'dibuat_bulan_ini' => $spkDibuatBulanIni->count(),
                'dibuat_selesai' => $spkDibuatBulanIni->where('status', StatusSpk::Selesai)->count(),
                'dibuat_aktif' => $spkDibuatBulanIni->where('status', StatusSpk::Aktif)->count(),
                'dibuat_dibatalkan' => $spkDibuatBulanIni->where('status', StatusSpk::Dibatalkan)->count(),
            ],
            'spkSelesaiBulanIni' => $spkSelesaiBulanIni,
            'spkAktif' => $spkAktif,
            'kendalaBulanIni' => Kendala::whereBetween('created_at', [$awal, $akhir])->count(),
            'temuanBulanIni' => LaporanKondisi::whereBetween('created_at', [$awal, $akhir])->count(),
        ];
    }
}
