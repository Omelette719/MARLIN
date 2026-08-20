<?php

namespace App\Livewire\User;

use App\Enums\StatusRambuPasang;
use App\Enums\StatusSpk;
use App\Enums\Urgensi;
use App\Models\DikerjakanOleh;
use App\Models\JenisRambu;
use App\Models\Rambu;
use App\Models\RambuPasang;
use App\Models\Spk;
use App\Support\PetaData;
use App\Support\SpkProgressStatus;
use App\Support\WilayahBanjarmasin;
use Flux\Flux;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Daftar Surat Aktif')]
class Dashboard extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    // Fired instead of a real link when the download button is shown to a
    // petugas who hasn't joined this SPK's team — SuratPengantarController
    // would otherwise 403 them with a bare error page for a case that's
    // completely expected here (dashboard lists ALL active SPKs, not just
    // joined ones).
    public function tautanSuratPengantarDitolak(): void
    {
        Flux::toast(variant: 'warning', text: 'Gabung dulu ke tim surat ini sebelum bisa mengunduh surat pengantarnya.');
    }

    // Mirrors Admin\Dashboard::spkPrioritas() exactly (same "urgent first,
    // then lowest progress" sort), just scoped down to SPK this petugas has
    // actually joined — the admin's version deliberately looks at every
    // active SPK since admin oversight isn't per-team, but a petugas's own
    // "needs attention" list should only ever be about their own work.
    private function spkPerluPerhatian(Collection $joinedSpkIds): Collection
    {
        return Spk::where('status', StatusSpk::Aktif)
            ->whereIn('id', $joinedSpkIds)
            ->withCount('rambuPasang')
            ->with(['rambuPasang' => fn ($q) => $q->select('id', 'rambu_spk_id', 'status')])
            ->get()
            ->map(function (Spk $spk) {
                $rambuAktif = $spk->rambuPasang->where('status', '!=', 'batal');
                $total = $rambuAktif->count();
                $selesai = $rambuAktif->where('status', 'selesai')->count();

                return [
                    'spk' => $spk,
                    'selesai' => $selesai,
                    'total' => $total,
                    'progres' => $total > 0 ? $selesai / $total : 1,
                    'butuhPerhatian' => $spk->prioritas || $spk->urgensiSaatIni() === Urgensi::Tinggi,
                ];
            })
            ->sort(fn ($a, $b) => match (true) {
                $a['butuhPerhatian'] !== $b['butuhPerhatian'] => $a['butuhPerhatian'] ? -1 : 1,
                $a['butuhPerhatian'] => $a['spk']->deadline <=> $b['spk']->deadline,
                default => $a['progres'] <=> $b['progres'] ?: $a['spk']->deadline <=> $b['spk']->deadline,
            })
            ->take(5)
            ->values();
    }

    // Suggests unclaimed work "close to" what this petugas is already doing:
    // same kecamatan first (a literal match on the kecamatan derived from
    // each rambu's kelurahan), then whatever's geographically nearest by
    // real coordinates to the centroid of the petugas's own active rambu —
    // real distance rather than a hand-built kecamatan-adjacency table, so
    // a candidate just across a kecamatan border still ranks sensibly. Falls
    // back to soonest-deadline-first when the petugas has no active work of
    // their own yet to compare against (nothing to be "close to").
    private function saranSpk(Collection $joinedSpkIds): Collection
    {
        $rambuSaya = Rambu::whereHas('rambuPasang', fn ($q) => $q
            ->whereIn('rambu_spk_id', $joinedSpkIds)
            ->where('status', '!=', StatusRambuPasang::Batal)
            ->whereHas('spk', fn ($q2) => $q2->where('status', StatusSpk::Aktif))
        )->get(['kelurahan', 'koordinat']);

        $kecamatanSaya = $rambuSaya
            ->map(fn (Rambu $r) => WilayahBanjarmasin::kecamatanFromKelurahan($r->kelurahan))
            ->filter()
            ->unique();

        $titikSaya = $rambuSaya
            ->map(fn (Rambu $r) => Rambu::parseKoordinat($r->koordinat))
            ->filter();

        $pusatSaya = $titikSaya->isNotEmpty()
            ? [$titikSaya->avg(fn ($t) => $t[0]), $titikSaya->avg(fn ($t) => $t[1])]
            : null;

        return Spk::where('status', StatusSpk::Aktif)
            ->whereNotIn('id', $joinedSpkIds)
            ->whereDoesntHave('dikerjakanOleh')
            ->withCount('rambuPasang')
            ->with(['rambuPasang.rambu:id,kelurahan,koordinat'])
            ->get()
            ->map(function (Spk $spk) use ($kecamatanSaya, $pusatSaya) {
                $rambu = $spk->rambuPasang->pluck('rambu')->filter();

                $kecamatanSpk = $rambu
                    ->map(fn (Rambu $r) => WilayahBanjarmasin::kecamatanFromKelurahan($r->kelurahan))
                    ->filter()
                    ->unique();

                $jarakMeter = null;

                if ($pusatSaya) {
                    $jarakMeter = $rambu
                        ->map(fn (Rambu $r) => Rambu::parseKoordinat($r->koordinat))
                        ->filter()
                        ->map(fn ($t) => Rambu::jarakMeter($pusatSaya[0], $pusatSaya[1], $t[0], $t[1]))
                        ->min();
                }

                return [
                    'spk' => $spk,
                    'samaKecamatan' => $kecamatanSaya->isNotEmpty() && $kecamatanSpk->intersect($kecamatanSaya)->isNotEmpty(),
                    'jarakMeter' => $jarakMeter,
                ];
            })
            ->sort(fn ($a, $b) => match (true) {
                $a['samaKecamatan'] !== $b['samaKecamatan'] => $a['samaKecamatan'] ? -1 : 1,
                $a['jarakMeter'] !== null && $b['jarakMeter'] !== null => $a['jarakMeter'] <=> $b['jarakMeter'],
                $a['jarakMeter'] !== null => -1,
                $b['jarakMeter'] !== null => 1,
                default => $a['spk']->deadline <=> $b['spk']->deadline,
            })
            ->take(3)
            ->values();
    }

    public function with(): array
    {
        $joinedSpkIds = DikerjakanOleh::where('by_user_id', Auth::id())->pluck('by_spk_id');

        $spk = Spk::query()
            ->where('status', StatusSpk::Aktif)
            ->when($this->search, fn ($query) => $query->where(fn ($q) => $q
                ->where('nomor_surat', 'like', "%{$this->search}%")
                ->orWhere('wilayah', 'like', "%{$this->search}%")))
            ->withCount(['rambuPasang', 'dikerjakanOleh'])
            ->with(['rambuPasang:id,rambu_spk_id,status,jenis_pekerjaan,foto_survei'])
            ->orderByDesc('prioritas')
            ->orderBy('deadline')
            ->paginate(9);

        $spk->getCollection()->transform(function (Spk $item) {
            $statuses = $item->rambuPasang->pluck('status')->reject(fn ($s) => $s === StatusRambuPasang::Batal);

            $item->progress_status = SpkProgressStatus::hitung($item, $statuses);
            $item->siap_diajukan = SpkProgressStatus::siapDiajukan($item, $statuses);

            $item->cover_photos = $item->rambuPasang->pluck('foto_survei')->filter()->unique()->values();

            return $item;
        });

        $joinedRambuPasang = RambuPasang::whereIn('rambu_spk_id', $joinedSpkIds);

        // Aktif/Progres/Mendekati Deadline summarize the whole "papan
        // pekerjaan" shown below (every active SPK, joined or not), not just
        // this petugas's own team — otherwise the cards read as wrong before
        // a petugas has joined anything, even though the list right under
        // them is already showing every active surat. Selesai Bulan Ini
        // stays personal on purpose: it's a historical tally of this
        // petugas's own completed work, which by definition no longer shows
        // in the active list at all once its SPK is fully Selesai.
        $spkAktif = Spk::where('status', StatusSpk::Aktif)->withCount('dikerjakanOleh')->get();

        return [
            'spk' => $spk,
            'joinedSpkIds' => $joinedSpkIds,
            'spkPerluPerhatian' => $this->spkPerluPerhatian($joinedSpkIds),
            'saranSpk' => $this->saranSpk($joinedSpkIds),
            'aktifCount' => $spkAktif->count(),
            'progresCount' => $spkAktif->where('dikerjakan_oleh_count', '>', 0)->count(),
            'mendekatiDeadlineCount' => $spkAktif->filter(fn (Spk $s) => $s->prioritas || $s->urgensiSaatIni() === Urgensi::Tinggi)->count(),
            'selesaiBulanIniCount' => (clone $joinedRambuPasang)
                ->where('status', StatusRambuPasang::Selesai->value)
                ->whereBetween('updated_at', [now()->startOfMonth(), now()->endOfMonth()])
                ->count(),
            'jenisRambuOptions' => JenisRambu::orderBy('nama_jenis')->get(),
            'tingkatOptions' => PetaData::TINGKAT_LABELS,
            'kecamatanOptions' => WilayahBanjarmasin::kecamatanOptions(),
            'kelurahanOptions' => WilayahBanjarmasin::kelurahanOptions(),
        ];
    }

    public function render()
    {
        return view('pages::user.dashboard');
    }
}
