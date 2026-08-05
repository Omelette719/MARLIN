<?php

namespace App\Models;

use App\Concerns\ComposesWilayah;
use App\Enums\KondisiRambu;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

#[Table('rambu')]
#[Fillable(['jenis_rambu_id', 'wilayah', 'jalan', 'rt', 'kelurahan', 'lokasi', 'koordinat', 'kondisi_terkini', 'sudah_terpasang'])]
class Rambu extends Model
{
    use ComposesWilayah;

    protected function casts(): array
    {
        return [
            'kondisi_terkini' => KondisiRambu::class,
            'sudah_terpasang' => 'boolean',
        ];
    }

    public function jenisRambu(): BelongsTo
    {
        return $this->belongsTo(JenisRambu::class);
    }

    public function rambuPasang(): HasMany
    {
        return $this->hasMany(RambuPasang::class);
    }

    public function laporanKondisi(): HasMany
    {
        return $this->hasMany(LaporanKondisi::class);
    }

    /**
     * Best real photo available for this rambu: the most recent survey photo,
     * falling back to the most recent completed-work photo, across its full
     * rambu_pasang history — never the jenis_rambu icon graphic.
     */
    public function fotoUtama(): ?string
    {
        $riwayat = $this->relationLoaded('rambuPasang')
            ? $this->rambuPasang->sortByDesc('created_at')
            : $this->rambuPasang()->latest()->with('laporanPengerjaan')->get();

        foreach ($riwayat as $rp) {
            if (filled($rp->foto_survei)) {
                return $rp->foto_survei;
            }

            $laporan = $rp->relationLoaded('laporanPengerjaan')
                ? $rp->laporanPengerjaan->sortByDesc('created_at')->first()
                : $rp->laporanPengerjaan()->latest()->first();

            if ($laporan && filled($laporan->foto_sesudah)) {
                return $laporan->foto_sesudah;
            }
        }

        return null;
    }

    /**
     * Shared by the peta pin builder, the Koordinat validation rule, and
     * terdekat() below, so "what counts as a valid coordinate" only lives
     * in one place. Returns [lat, lng] as floats, or null if unparsable.
     */
    public static function parseKoordinat(?string $koordinat): ?array
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

    public static function jarakMeter(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $radiusBumiMeter = 6371000;

        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return $radiusBumiMeter * (2 * atan2(sqrt($a), sqrt(1 - $a)));
    }

    /**
     * Rambu already registered within $meter of the given coordinate string.
     * Used to warn admins before they accidentally register a sign that's
     * already in the system a few meters away under a different entry.
     *
     * @return Collection<int, Rambu> each with a jarak_meter attribute attached
     */
    public static function terdekat(string $koordinat, float $meter = 20.0, ?int $kecualikanId = null): Collection
    {
        $target = self::parseKoordinat($koordinat);

        if (! $target) {
            return collect();
        }

        [$lat, $lng] = $target;

        return self::query()
            ->with('jenisRambu')
            ->when($kecualikanId, fn ($q) => $q->where('id', '!=', $kecualikanId))
            ->get()
            ->map(function (self $rambu) use ($lat, $lng) {
                $coords = self::parseKoordinat($rambu->koordinat);

                if (! $coords) {
                    return null;
                }

                $rambu->jarak_meter = self::jarakMeter($lat, $lng, $coords[0], $coords[1]);

                return $rambu;
            })
            ->filter()
            ->filter(fn (self $rambu) => $rambu->jarak_meter <= $meter)
            ->sortBy('jarak_meter')
            ->values();
    }
}
