<?php

namespace App\Support;

use App\Enums\StatusRambuPasang;
use App\Models\RambuPasang;

class LaporanRambu
{
    /**
     * Build a flexible rambu work-history listing: filterable by jenis rambu,
     * StatusRambuPasang, and a date range (on rambu_pasang.created_at). Every
     * filter is optional — omitting one means "semua". Shared by the admin
     * preview page, the standalone PDF export, and embedded (via LaporanBulanan)
     * into the monthly report's rambu-detail section, so all three always
     * show identical numbers for the same filters.
     *
     * @param  array{tanggal_dari?: ?string, tanggal_sampai?: ?string, jenis_rambu_id?: mixed, status?: ?string}  $filters
     */
    public static function build(array $filters): array
    {
        $tanggalDari = $filters['tanggal_dari'] ?? null;
        $tanggalSampai = $filters['tanggal_sampai'] ?? null;
        $jenisRambuId = $filters['jenis_rambu_id'] ?? null;
        $status = $filters['status'] ?? null;

        $items = RambuPasang::query()
            ->with(['rambu.jenisRambu', 'spk'])
            ->when($jenisRambuId, fn ($q) => $q->whereHas('rambu', fn ($r) => $r->where('jenis_rambu_id', $jenisRambuId)))
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($tanggalDari, fn ($q) => $q->whereDate('created_at', '>=', $tanggalDari))
            ->when($tanggalSampai, fn ($q) => $q->whereDate('created_at', '<=', $tanggalSampai))
            ->orderBy('created_at')
            ->get();

        return [
            'items' => $items,
            'total' => $items->count(),
            'perStatus' => collect(StatusRambuPasang::cases())->mapWithKeys(fn ($s) => [
                $s->value => $items->where('status', $s)->count(),
            ]),
            'filters' => [
                'tanggal_dari' => $tanggalDari,
                'tanggal_sampai' => $tanggalSampai,
                'jenis_rambu_id' => $jenisRambuId,
                'status' => $status,
            ],
        ];
    }
}
