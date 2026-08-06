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
     * Build the work-history report data for a free date range, optionally
     * narrowed by jenis rambu (applies to both the asset counts and the SPK
     * counts) and/or a RambuPasang status (applies to the embedded rambu
     * detail listing only, since SPK-level status is a different enum).
     * Shared by the admin preview page and the PDF export so both always
     * show identical numbers.
     *
     * @param  array{tanggal_dari?: ?string, tanggal_sampai?: ?string, jenis_rambu_id?: mixed, status?: ?string}  $filters
     */
    public static function build(array $filters): array
    {
        $awal = ! empty($filters['tanggal_dari'])
            ? Carbon::parse($filters['tanggal_dari'])->startOfDay()
            : now()->startOfMonth();

        $akhir = ! empty($filters['tanggal_sampai'])
            ? Carbon::parse($filters['tanggal_sampai'])->endOfDay()
            : now()->endOfDay();

        $jenisRambuId = $filters['jenis_rambu_id'] ?? null;
        $status = $filters['status'] ?? null;

        $rambuQuery = fn () => Rambu::query()->when($jenisRambuId, fn ($q) => $q->where('jenis_rambu_id', $jenisRambuId));
        $rambuTotal = $rambuQuery()->count();
        $rambuTerpasang = $rambuQuery()->where('sudah_terpasang', true)->count();

        $spkQuery = fn () => Spk::query()->when(
            $jenisRambuId,
            fn ($q) => $q->whereHas('rambuPasang.rambu', fn ($r) => $r->where('jenis_rambu_id', $jenisRambuId))
        );

        $spkDibuatPeriode = $spkQuery()->whereBetween('created_at', [$awal, $akhir])->get();

        $spkSelesaiPeriode = $spkQuery()->where('status', StatusSpk::Selesai)
            ->whereBetween('updated_at', [$awal, $akhir])
            ->withCount('rambuPasang')
            ->orderBy('nomor_surat')
            ->get();

        // Null-filtered explicitly (not Collection::filter()'s default falsy
        // check) because a 0-day selisih (finished exactly on the deadline)
        // is a real, meaningful value that must not be dropped.
        $durasiList = $spkSelesaiPeriode->map(fn (Spk $spk) => $spk->durasiPengerjaanHari())->filter(fn (?int $v) => $v !== null);
        $selisihList = $spkSelesaiPeriode->map(fn (Spk $spk) => $spk->selisihDeadlineHari())->filter(fn (?int $v) => $v !== null);

        $spkAktif = $spkQuery()->where('status', StatusSpk::Aktif)
            ->where('created_at', '<=', $akhir)
            ->withCount('rambuPasang')
            ->with(['rambuPasang' => fn ($q) => $q->select('id', 'rambu_spk_id', 'status')])
            ->orderBy('deadline')
            ->get()
            ->each(function (Spk $spk) {
                $spk->selesai_count = $spk->rambuPasang->where('status', 'selesai')->count();
            });

        return [
            'awal' => $awal,
            'akhir' => $akhir,
            'periodeLabel' => $awal->translatedFormat('d M Y').' - '.$akhir->translatedFormat('d M Y'),
            'jenisRambuId' => $jenisRambuId,
            'status' => $status,
            'rambu' => [
                'total' => $rambuTotal,
                'terpasang' => $rambuTerpasang,
                'belum_terpasang' => $rambuTotal - $rambuTerpasang,
                'kondisi_baik' => $rambuQuery()->where('kondisi_terkini', 'baik')->count(),
                'kondisi_rusak' => $rambuQuery()->where('kondisi_terkini', 'rusak')->count(),
            ],
            'spk' => [
                'dibuat_periode' => $spkDibuatPeriode->count(),
                'dibuat_selesai' => $spkDibuatPeriode->where('status', StatusSpk::Selesai)->count(),
                'dibuat_aktif' => $spkDibuatPeriode->where('status', StatusSpk::Aktif)->count(),
                'dibuat_dibatalkan' => $spkDibuatPeriode->where('status', StatusSpk::Dibatalkan)->count(),
            ],
            'spkSelesaiPeriode' => $spkSelesaiPeriode,
            'analitikSelesai' => [
                'rata_rata_durasi_hari' => $durasiList->isNotEmpty() ? round($durasiList->avg(), 1) : null,
                'rata_rata_selisih_deadline_hari' => $selisihList->isNotEmpty() ? round($selisihList->avg(), 1) : null,
                'tepat_waktu_count' => $selisihList->filter(fn (int $v) => $v <= 0)->count(),
                'terlambat_count' => $selisihList->filter(fn (int $v) => $v > 0)->count(),
            ],
            'spkAktif' => $spkAktif,
            'kendalaPeriode' => Kendala::whereBetween('created_at', [$awal, $akhir])->count(),
            'temuanPeriode' => LaporanKondisi::whereBetween('created_at', [$awal, $akhir])->count(),
            'rambuDetail' => LaporanRambu::build([
                'tanggal_dari' => $awal->toDateString(),
                'tanggal_sampai' => $akhir->toDateString(),
                'jenis_rambu_id' => $jenisRambuId,
                'status' => $status,
            ]),
        ];
    }
}
