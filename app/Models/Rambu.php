<?php

namespace App\Models;

use App\Concerns\ComposesWilayah;
use App\Enums\KondisiRambu;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
}
