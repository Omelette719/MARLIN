<?php

namespace App\Models;

use App\Enums\StatusLaporan;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('laporan_pengerjaan')]
#[Fillable([
    'rambu_pasang_id', 'dilaporkan_oleh', 'foto_sesudah', 'koordinat_gps',
    'catatan_lapangan', 'status', 'catatan_penolakan', 'divalidasi_oleh', 'divalidasi_pada',
])]
class LaporanPengerjaan extends Model
{
    protected function casts(): array
    {
        return [
            'status' => StatusLaporan::class,
            'divalidasi_pada' => 'datetime',
        ];
    }

    public function rambuPasang(): BelongsTo
    {
        return $this->belongsTo(RambuPasang::class);
    }

    public function pelapor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dilaporkan_oleh');
    }

    public function validator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'divalidasi_oleh');
    }

    public function barangBahan(): HasMany
    {
        return $this->hasMany(BarangBahan::class);
    }
}
