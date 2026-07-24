<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('barang_bahan')]
#[Fillable(['laporan_pengerjaan_id', 'nama', 'jumlah', 'satuan'])]
class BarangBahan extends Model
{
    const UPDATED_AT = null;

    public function laporanPengerjaan(): BelongsTo
    {
        return $this->belongsTo(LaporanPengerjaan::class);
    }
}
