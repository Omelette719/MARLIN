<?php

namespace App\Models;

use App\Concerns\ComposesWilayah;
use App\Enums\AsalPermintaan;
use App\Enums\JenisPekerjaan;
use App\Enums\StatusSpk;
use App\Enums\Urgensi;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('spk')]
#[Fillable([
    'nomor_surat', 'dibuat_oleh', 'wilayah', 'jalan', 'rt', 'kelurahan', 'deadline', 'deadline_asli', 'prioritas',
    'urgensi', 'status', 'asal_permintaan', 'keterangan_asal', 'perihal', 'tanggal_survei',
    'petugas_survei', 'file_referensi', 'catatan_pekerja_tambahan', 'laporan_akhir_diajukan_at', 'selesai_pada',
])]
class Spk extends Model
{
    use ComposesWilayah;

    protected function casts(): array
    {
        return [
            'deadline' => 'date',
            'deadline_asli' => 'date',
            'prioritas' => 'boolean',
            'urgensi' => Urgensi::class,
            'status' => StatusSpk::class,
            'asal_permintaan' => AsalPermintaan::class,
            'tanggal_survei' => 'date',
            'laporan_akhir_diajukan_at' => 'datetime',
            'selesai_pada' => 'datetime',
        ];
    }

    // Days from creation to completion — only meaningful once selesai_pada
    // is set (i.e. every rambu under this SPK reached Selesai/Batal; see
    // Admin\Validasi\Show::finalize()). Null for SPKs not yet finished, and
    // for any finished before this column existed.
    public function durasiPengerjaanHari(): ?int
    {
        if (! $this->selesai_pada) {
            return null;
        }

        return $this->created_at->copy()->startOfDay()->diffInDays($this->selesai_pada->copy()->startOfDay());
    }

    // Positive: finished N days after the deadline (late). Negative:
    // finished N days before the deadline (early). Zero: finished on the
    // deadline day itself.
    public function selisihDeadlineHari(): ?int
    {
        if (! $this->selesai_pada) {
            return null;
        }

        return $this->deadline->copy()->startOfDay()->diffInDays($this->selesai_pada->copy()->startOfDay(), false);
    }

    // Human-readable Indonesian summary of selisihDeadlineHari(), so every
    // display surface (SPK detail pages, future dashboard/laporan work)
    // shows the exact same wording instead of re-deriving it from the sign.
    public function selisihDeadlineLabel(): ?string
    {
        $selisih = $this->selisihDeadlineHari();

        if ($selisih === null) {
            return null;
        }

        if ($selisih === 0) {
            return 'Tepat waktu';
        }

        return $selisih > 0
            ? "Terlambat {$selisih} hari dari deadline"
            : abs($selisih).' hari lebih cepat dari deadline';
    }

    // The `urgensi` column is only ever recomputed on a write (create, edit,
    // a priority SPK pushing others out, or an admin extending the deadline
    // during validasi) — it does NOT advance on its own as today's date
    // moves past the deadline. Left untouched, an active SPK's badge (and
    // its peta pin color, and whether it's flagged "butuh perhatian" on the
    // admin dashboard) can go stale and understate how overdue it actually
    // is. This recomputes live for active SPKs so every display surface
    // reads correctly regardless of how long it's been since anyone last
    // touched the record; finished/cancelled SPKs keep their frozen stored
    // value instead, since recalculating urgency against today's date for
    // work that's no longer actionable wouldn't mean anything.
    public function urgensiSaatIni(): Urgensi
    {
        if ($this->status !== StatusSpk::Aktif) {
            return $this->urgensi;
        }

        // Cast defensively: a model instance built from Spk::create() without
        // an explicit 'prioritas' key holds null here until the row is
        // re-fetched, even though the column itself defaults to false in the
        // database — computeUrgensi() requires a real bool.
        return self::computeUrgensi($this->deadline, (bool) $this->prioritas);
    }

    // Shared by Create/Edit (manual save) and PenyesuaianDeadlineSpk (automatic
    // push from a newly-prioritized SPK) so urgensi is always derived the same
    // way no matter which of those three places last touched the deadline.
    public static function computeUrgensi(CarbonInterface $deadline, bool $prioritas): Urgensi
    {
        if ($prioritas) {
            return Urgensi::Tinggi;
        }

        $daysUntilDeadline = now()->startOfDay()->diffInDays($deadline->copy()->startOfDay(), false);

        return match (true) {
            $daysUntilDeadline <= 2 => Urgensi::Tinggi,
            $daysUntilDeadline <= 7 => Urgensi::Sedang,
            default => Urgensi::Rendah,
        };
    }

    // Each rambu_pasang row now picks its own jenis_pekerjaan independently
    // (an SPK is no longer forced to be entirely Pasang Baru or entirely
    // Perbaikan) — this collapses that back into a single value for display
    // surfaces that show one badge per SPK, returning null when the rows
    // are genuinely mixed so callers can show a "Campuran" state instead of
    // picking one type arbitrarily.
    public function jenisRingkasan(): ?JenisPekerjaan
    {
        $jenisUnik = $this->rambuPasang->pluck('jenis_pekerjaan')->unique();

        return $jenisUnik->count() === 1 ? $jenisUnik->first() : null;
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
