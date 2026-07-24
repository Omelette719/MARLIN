<?php

namespace App\Models;

use App\Enums\KondisiRambu;
use App\Enums\StatusTindakLanjut;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('laporan_kondisi')]
#[Fillable([
    'rambu_id', 'dilaporkan_oleh', 'kondisi_dilaporkan', 'foto', 'catatan',
    'status_tindak_lanjut', 'ditindaklanjuti_oleh',
])]
class LaporanKondisi extends Model
{
    protected function casts(): array
    {
        return [
            'kondisi_dilaporkan' => KondisiRambu::class,
            'status_tindak_lanjut' => StatusTindakLanjut::class,
        ];
    }

    public function rambu(): BelongsTo
    {
        return $this->belongsTo(Rambu::class);
    }

    public function pelapor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dilaporkan_oleh');
    }

    public function penindaklanjut(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ditindaklanjuti_oleh');
    }

    public function rambuPasang(): HasMany
    {
        return $this->hasMany(RambuPasang::class, 'laporan_kondisi_id');
    }
}
