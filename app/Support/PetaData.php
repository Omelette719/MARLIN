<?php

namespace App\Support;

use App\Enums\StatusRambuPasang;
use App\Enums\StatusSpk;
use App\Models\Rambu;
use App\Models\Spk;
use Illuminate\Support\Facades\Storage;

class PetaData
{
    public const TINGKAT_LABELS = [
        'tinggi' => 'Tinggi / Prioritas',
        'sedang' => 'Sedang',
        'menunggu_validasi' => 'Menunggu Validasi',
        'selesai' => 'Selesai / Kondisi Baik',
        'rendah' => 'Rendah',
    ];

    /**
     * Shared by the peta JSON endpoint (PetaController) and the PDF export
     * (PetaExportController) so both always show the same pins for the same
     * filters. Every filter is optional — omitting one means "semua".
     *
     * @param  array{jenis_rambu_id?: mixed, tingkat?: ?string, kelurahan?: ?string, kecamatan?: ?string}  $filters
     */
    public static function build(array $filters): array
    {
        $jenisRambuId = $filters['jenis_rambu_id'] ?? null;
        $tingkat = $filters['tingkat'] ?? null;
        $kelurahan = $filters['kelurahan'] ?? null;
        $kecamatan = $filters['kecamatan'] ?? null;

        $pins = Rambu::with([
            'jenisRambu',
            'rambuPasang' => fn ($q) => $q->latest()->with(['spk', 'laporanPengerjaan']),
        ])
            ->when($jenisRambuId, fn ($q) => $q->where('jenis_rambu_id', $jenisRambuId))
            ->when($kelurahan, fn ($q) => $q->where('kelurahan', $kelurahan))
            ->when($kecamatan, fn ($q) => $q->whereIn('kelurahan', WilayahBanjarmasin::kelurahanByKecamatan($kecamatan)))
            ->get()
            ->map(fn (Rambu $rambu) => self::toPin($rambu))
            ->filter()
            ->when($tingkat, fn ($pins) => $pins->where('tingkat', $tingkat))
            ->values();

        return [
            'pins' => $pins,
            'total' => $pins->count(),
            'perTingkat' => collect(array_keys(self::TINGKAT_LABELS))->mapWithKeys(fn ($t) => [
                $t => $pins->where('tingkat', $t)->count(),
            ]),
            'perJenis' => $pins->groupBy('jenis_rambu')->map->count(),
            'perKecamatan' => $pins
                ->groupBy(fn (array $p) => WilayahBanjarmasin::kecamatanFromKelurahan($p['kelurahan']) ?? 'Tidak diketahui')
                ->map->count(),
            // Only for the PDF export's "Daftar SPK" table — the SPK behind
            // every filtered pin, restricted to Aktif (a finished/cancelled
            // SPK isn't work still on the table, so it has no place in a
            // report about what's currently on the filtered map).
            'spkTerkait' => Spk::whereIn('id', $pins->pluck('spk.id')->filter()->unique()->values())
                ->where('status', StatusSpk::Aktif)
                ->orderBy('deadline')
                ->get(),
            'filters' => [
                'jenis_rambu_id' => $jenisRambuId,
                'tingkat' => $tingkat,
                'kelurahan' => $kelurahan,
                'kecamatan' => $kecamatan,
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

        $coords = Rambu::parseKoordinat($rambu->koordinat);

        if (! $coords) {
            return null;
        }

        $foto = $rambu->fotoUtama();

        $pin = [
            'id' => $rambu->id,
            'lat' => $coords[0],
            'lng' => $coords[1],
            'wilayah' => $rambu->wilayah,
            'kelurahan' => $rambu->kelurahan,
            'lokasi' => $rambu->lokasi,
            'jenis_rambu' => $rambu->jenisRambu?->nama_jenis,
            'ikon' => $rambu->jenisRambu?->gambar_referensi ? Storage::url($rambu->jenisRambu->gambar_referensi) : null,
            'foto' => $foto ? Storage::url($foto) : null,
            'bentuk_ikon' => $rambu->jenisRambu?->bentuk_ikon?->value ?? 'bulat',
            'kondisi_terkini' => $rambu->kondisi_terkini->value,
            'sudah_terpasang' => $rambu->sudah_terpasang,
            'status' => $task?->status->value,
            'jenis_pekerjaan' => $task?->jenis_pekerjaan->value,
            'spk' => $task?->spk ? [
                'id' => $task->spk->id,
                'nomor_surat' => $task->spk->nomor_surat,
                'prioritas' => $task->spk->prioritas,
                'urgensi' => $task->spk->urgensiSaatIni()->value,
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
            return 'tinggi';
        }

        if (($pin['status'] === 'selesai' || $pin['status'] === null) && $pin['kondisi_terkini'] === 'baik') {
            return 'selesai';
        }

        if ($spk && $spk['urgensi'] === 'sedang') {
            return 'sedang';
        }

        return 'rendah';
    }
}
