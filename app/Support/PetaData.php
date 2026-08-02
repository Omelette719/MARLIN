<?php

namespace App\Support;

use App\Enums\StatusRambuPasang;
use App\Models\Rambu;
use Illuminate\Support\Facades\Storage;

class PetaData
{
    public const TINGKAT_LABELS = [
        'urgent' => 'Urgent / Prioritas / Tinggi',
        'rusak' => 'Rusak / Perbaikan Berjalan',
        'menunggu_validasi' => 'Menunggu Validasi',
        'selesai' => 'Selesai / Kondisi Baik',
        'belum' => 'Belum Dikerjakan',
    ];

    /**
     * Shared by the peta JSON endpoint (PetaController) and the PDF export
     * (PetaExportController) so both always show the same pins for the same
     * filters. Every filter is optional — omitting one means "semua".
     *
     * @param  array{jenis_rambu_id?: mixed, tingkat?: ?string, tanggal_dari?: ?string, tanggal_sampai?: ?string}  $filters
     */
    public static function build(array $filters): array
    {
        $jenisRambuId = $filters['jenis_rambu_id'] ?? null;
        $tingkat = $filters['tingkat'] ?? null;
        $tanggalDari = $filters['tanggal_dari'] ?? null;
        $tanggalSampai = $filters['tanggal_sampai'] ?? null;

        $pins = Rambu::with([
            'jenisRambu',
            'rambuPasang' => fn ($q) => $q->latest()->with(['spk', 'laporanPengerjaan']),
        ])
            ->when($jenisRambuId, fn ($q) => $q->where('jenis_rambu_id', $jenisRambuId))
            ->get()
            ->map(fn (Rambu $rambu) => self::toPin($rambu))
            ->filter()
            ->when($tanggalDari || $tanggalSampai, fn ($pins) => $pins->filter(function (array $pin) use ($tanggalDari, $tanggalSampai) {
                if (! $pin['task_created_at']) {
                    return false;
                }

                if ($tanggalDari && $pin['task_created_at'] < $tanggalDari) {
                    return false;
                }

                if ($tanggalSampai && $pin['task_created_at'] > $tanggalSampai) {
                    return false;
                }

                return true;
            }))
            ->when($tingkat, fn ($pins) => $pins->where('tingkat', $tingkat))
            ->values();

        return [
            'pins' => $pins,
            'total' => $pins->count(),
            'perTingkat' => collect(array_keys(self::TINGKAT_LABELS))->mapWithKeys(fn ($t) => [
                $t => $pins->where('tingkat', $t)->count(),
            ]),
            'perJenis' => $pins->groupBy('jenis_rambu')->map->count(),
            'perWilayah' => $pins->groupBy('wilayah')->map->count(),
            'filters' => [
                'jenis_rambu_id' => $jenisRambuId,
                'tingkat' => $tingkat,
                'tanggal_dari' => $tanggalDari,
                'tanggal_sampai' => $tanggalSampai,
            ],
        ];
    }

    private static function toPin(Rambu $rambu): ?array
    {
        $task = $rambu->rambuPasang
            ->reject(fn ($t) => $t->status === StatusRambuPasang::Batal)
            ->first();

        if (! $task && ! $rambu->sudah_terpasang) {
            return null;
        }

        $coords = self::parseKoordinat($rambu->koordinat);

        if (! $coords) {
            return null;
        }

        $foto = $rambu->fotoUtama();

        $pin = [
            'id' => $rambu->id,
            'lat' => $coords[0],
            'lng' => $coords[1],
            'wilayah' => $rambu->wilayah,
            'lokasi' => $rambu->lokasi,
            'jenis_rambu' => $rambu->jenisRambu?->nama_jenis,
            'ikon' => $rambu->jenisRambu?->gambar_referensi ? Storage::url($rambu->jenisRambu->gambar_referensi) : null,
            'foto' => $foto ? Storage::url($foto) : null,
            'bentuk_ikon' => $rambu->jenisRambu?->bentuk_ikon?->value ?? 'bulat',
            'kondisi_terkini' => $rambu->kondisi_terkini->value,
            'sudah_terpasang' => $rambu->sudah_terpasang,
            'status' => $task?->status->value,
            'jenis_pekerjaan' => $task?->jenis_pekerjaan->value,
            'task_created_at' => $task?->created_at?->toDateString(),
            'spk' => $task?->spk ? [
                'id' => $task->spk->id,
                'nomor_surat' => $task->spk->nomor_surat,
                'prioritas' => $task->spk->prioritas,
                'urgensi' => $task->spk->urgensi->value,
                'deadline' => $task->spk->deadline->format('Y-m-d'),
            ] : null,
        ];

        $pin['tingkat'] = self::tingkatUrgensi($pin);

        return $pin;
    }

    // Mirrors resources/js/app.js's pinColor() bucket priority exactly, so
    // filtering by "tingkat" server-side matches what the legend/marker color
    // shows on the map. menunggu_validasi is checked first on purpose (per
    // pinColor's own comment there) — once a report is submitted, that
    // progress takes priority over the SPK's own urgency for how the pin reads.
    private static function tingkatUrgensi(array $pin): string
    {
        $spk = $pin['spk'];

        if ($pin['status'] === 'menunggu_validasi') {
            return 'menunggu_validasi';
        }

        if ($pin['status'] === 'urgent' || ($spk && ($spk['prioritas'] || $spk['urgensi'] === 'tinggi'))) {
            return 'urgent';
        }

        if ($pin['kondisi_terkini'] === 'rusak' || ($pin['jenis_pekerjaan'] === 'perbaikan' && $pin['status'] !== 'selesai')) {
            return 'rusak';
        }

        if (($pin['status'] === 'selesai' || $pin['status'] === null) && $pin['kondisi_terkini'] === 'baik') {
            return 'selesai';
        }

        return 'belum';
    }

    private static function parseKoordinat(?string $koordinat): ?array
    {
        if (! $koordinat || ! str_contains($koordinat, ',')) {
            return null;
        }

        [$lat, $lng] = array_map('trim', explode(',', $koordinat, 2));

        if (! is_numeric($lat) || ! is_numeric($lng)) {
            return null;
        }

        return [(float) $lat, (float) $lng];
    }
}
