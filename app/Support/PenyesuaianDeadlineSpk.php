<?php

namespace App\Support;

use App\Enums\StatusSpk;
use App\Models\AuditLog;
use App\Models\Notifikasi;
use App\Models\Spk;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PenyesuaianDeadlineSpk
{
    /**
     * When an SPK is created/edited into `prioritas`, every other active,
     * non-prioritas SPK gets breathing room: its deadline may be pushed out
     * by half the new priority SPK's own day-count (floor'd — 2 hari → +1,
     * 10 hari → +5, 11 hari → +5, 7 hari → +3, 5 hari → +2, 3 hari → +1).
     *
     * This is deliberately a MAX against each SPK's own untouched baseline
     * (`deadline_asli`), never a running sum — otherwise every subsequent
     * priority SPK would keep piling more days on top of the last, and
     * deadlines would drift forever. Re-deriving `deadline_asli + tambahan`
     * and only applying it when it's later than the SPK's current deadline
     * means only the single most demanding priority SPK ever seen actually
     * sets the pushed deadline; smaller/older priority pushes are superseded,
     * not stacked. Other prioritas SPKs are excluded entirely — something
     * already flagged urgent shouldn't get diluted by yet another urgent job.
     */
    public static function terapkan(Spk $spkPrioritas): void
    {
        if (! $spkPrioritas->prioritas) {
            return;
        }

        $hari = now()->startOfDay()->diffInDays($spkPrioritas->deadline);
        $tambahan = intdiv($hari, 2);

        if ($tambahan < 1) {
            return;
        }

        Spk::where('status', StatusSpk::Aktif)
            ->where('id', '!=', $spkPrioritas->id)
            ->where('prioritas', false)
            ->get()
            ->each(function (Spk $target) use ($tambahan, $spkPrioritas) {
                $baseline = Carbon::parse($target->deadline_asli ?? $target->deadline)->startOfDay();
                $kandidat = $baseline->copy()->addDays($tambahan);

                if ($kandidat->lte(Carbon::parse($target->deadline)->startOfDay())) {
                    return;
                }

                $deadlineLama = $target->deadline->toDateString();

                DB::transaction(function () use ($target, $kandidat, $deadlineLama, $spkPrioritas) {
                    $target->update([
                        'deadline' => $kandidat,
                        'urgensi' => Spk::computeUrgensi($kandidat, false),
                    ]);

                    AuditLog::create([
                        'user_id' => Auth::id(),
                        'spk_id' => $target->id,
                        'aksi' => 'deadline_disesuaikan',
                        'keterangan' => "Deadline SPK {$target->nomor_surat} otomatis mundur dari {$deadlineLama} ke {$kandidat->toDateString()} karena SPK prioritas {$spkPrioritas->nomor_surat} dibuat.",
                    ]);

                    Notifikasi::create([
                        'user_id' => $target->dibuat_oleh,
                        'judul' => 'Deadline SPK Bergeser',
                        'pesan' => "Deadline SPK {$target->nomor_surat} otomatis mundur dari {$deadlineLama} ke {$kandidat->toDateString()} karena SPK prioritas {$spkPrioritas->nomor_surat} dibuat.",
                        'url' => route('admin.spk.show', $target),
                        'dibaca' => false,
                    ]);
                });
            });
    }
}
