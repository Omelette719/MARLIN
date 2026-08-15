<?php

namespace App\Support;

use App\Enums\StatusRambuPasang;
use App\Models\Spk;
use Illuminate\Support\Collection;

// Shared by User\Dashboard and User\SpkDikerjakan so the "what does this
// SPK's card badge say" logic can't drift between the two petugas-facing
// pages that both summarize a whole SPK's rambu_pasang rows into one status.
class SpkProgressStatus
{
    private const PRIORITAS = [
        StatusRambuPasang::Urgent,
        StatusRambuPasang::Tertunda,
        StatusRambuPasang::Revisi,
        StatusRambuPasang::MenungguValidasi,
        StatusRambuPasang::Belum,
        StatusRambuPasang::Selesai,
    ];

    // MenungguValidasi only means "the SPK is genuinely waiting on admin"
    // once the perwakilan has actually submitted Laporan Akhir (see the gate
    // explained in ALUR-BISNIS.md) — validation happens per-batch, not the
    // moment any single rambu's report comes in. Before that, a rambu
    // sitting at MenungguValidasi just means that one report is in, so it's
    // excluded from consideration here and the badge falls through to
    // whatever other status is still meaningful (Tertunda from a kendala
    // stays as-is, since that's worth surfacing regardless of the gate).
    public static function hitung(Spk $spk, Collection $statuses): StatusRambuPasang
    {
        if ($spk->laporan_akhir_diajukan_at) {
            return StatusRambuPasang::MenungguValidasi;
        }

        return collect(self::PRIORITAS)
            ->reject(fn ($p) => $p === StatusRambuPasang::MenungguValidasi)
            ->first(fn ($p) => $statuses->contains($p)) ?? StatusRambuPasang::Selesai;
    }

    // True once every rambu is individually handled (reported or flagged
    // with a kendala) but Laporan Akhir hasn't been submitted yet — the
    // "you're done working, now submit it" prompt shown alongside the badge
    // above so that badge falling back to Selesai in this state doesn't
    // read as "already validated".
    public static function siapDiajukan(Spk $spk, Collection $statuses): bool
    {
        return ! $spk->laporan_akhir_diajukan_at
            && $statuses->isNotEmpty()
            && $statuses->every(fn ($s) => in_array($s, [StatusRambuPasang::Tertunda, StatusRambuPasang::MenungguValidasi], true));
    }
}
