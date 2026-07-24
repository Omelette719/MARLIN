<?php

namespace App\Models;

use App\Enums\JenisPekerjaan;
use App\Enums\StatusRambuPasang;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('rambu_pasang')]
#[Fillable([
    'rambu_spk_id', 'rambu_id', 'laporan_kondisi_id', 'jenis_pekerjaan', 'jumlah',
    'foto_survei', 'catatan_instruksi', 'status',
])]
class RambuPasang extends Model
{
    protected function casts(): array
    {
        return [
            'jenis_pekerjaan' => JenisPekerjaan::class,
            'status' => StatusRambuPasang::class,
        ];
    }

    public function spk(): BelongsTo
    {
        return $this->belongsTo(Spk::class, 'rambu_spk_id');
    }

    public function rambu(): BelongsTo
    {
        return $this->belongsTo(Rambu::class);
    }

    public function laporanKondisi(): BelongsTo
    {
        return $this->belongsTo(LaporanKondisi::class);
    }

    public function laporanPengerjaan(): HasMany
    {
        return $this->hasMany(LaporanPengerjaan::class);
    }

    public function kendala(): HasMany
    {
        return $this->hasMany(Kendala::class);
    }
}
