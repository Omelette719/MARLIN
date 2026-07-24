<?php

namespace App\Models;

use App\Concerns\ComposesWilayah;
use App\Enums\AsalPermintaan;
use App\Enums\JenisPekerjaan;
use App\Enums\StatusSpk;
use App\Enums\Urgensi;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('spk')]
#[Fillable([
    'nomor_surat', 'dibuat_oleh', 'wilayah', 'jalan', 'rt', 'kelurahan', 'deadline', 'prioritas', 'urgensi',
    'status', 'jenis_spk', 'asal_permintaan', 'keterangan_asal', 'perihal', 'tanggal_survei', 'file_referensi',
    'catatan_pekerja_tambahan', 'laporan_akhir_diajukan_at',
])]
class Spk extends Model
{
    use ComposesWilayah;

    protected function casts(): array
    {
        return [
            'deadline' => 'date',
            'prioritas' => 'boolean',
            'urgensi' => Urgensi::class,
            'status' => StatusSpk::class,
            'jenis_spk' => JenisPekerjaan::class,
            'asal_permintaan' => AsalPermintaan::class,
            'tanggal_survei' => 'date',
            'laporan_akhir_diajukan_at' => 'datetime',
        ];
    }

    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }

    public function rambuPasang(): HasMany
    {
        return $this->hasMany(RambuPasang::class, 'rambu_spk_id');
    }

    public function dikerjakanOleh(): HasMany
    {
        return $this->hasMany(DikerjakanOleh::class, 'by_spk_id');
    }

    public function petugas(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'dikerjakan_oleh', 'by_spk_id', 'by_user_id')
            ->withPivot('is_perwakilan');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    public function rtPerwakilan(): HasMany
    {
        return $this->hasMany(RtPerwakilan::class, 'rtperwakilan_spk_id');
    }
}
