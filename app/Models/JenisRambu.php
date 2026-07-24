<?php

namespace App\Models;

use App\Enums\BentukIkon;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('jenis_rambu')]
#[Fillable(['nama_jenis', 'spesifikasi_standar', 'gambar_referensi', 'bentuk_ikon'])]
class JenisRambu extends Model
{
    protected function casts(): array
    {
        return [
            'bentuk_ikon' => BentukIkon::class,
        ];
    }

    public function rambu(): HasMany
    {
        return $this->hasMany(Rambu::class);
    }
}
