<?php

namespace App\Livewire\Admin\Validasi;

use App\Enums\JenisPekerjaan;
use App\Enums\KondisiRambu;
use App\Enums\StatusLaporan;
use App\Enums\StatusRambuPasang;
use App\Enums\StatusSpk;
use App\Models\AuditLog;
use App\Models\Notifikasi;
use App\Models\RambuPasang;
use App\Models\Spk;
use Carbon\Carbon;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Detail Validasi')]
class Show extends Component
{
    public Spk $spk;

    public array $checked = [];

    public array $catatanPenolakan = [];

    public bool $showPenolakanForm = false;

    // Pre-filled with the current deadline (not left blank) so the date
    // input reads as "here's the current value, change it if you want to
    // give leeway" rather than an empty field admin has to fill from scratch.
    public string $deadlineBaru = '';

    public bool $ubahDeadline = false;

    public function mount(Spk $spk): void
    {
        $this->spk = $spk;

        foreach ($this->pendingRambuPasang() as $rp) {
            $this->checked[$rp->id] = false;
        }
    }

    // A rambu can reach here two ways: a completed laporan_pengerjaan
    // (menunggu_validasi) or a logged kendala (tertunda) — both are reviewed
    // together in the same batch once the perwakilan submits the laporan akhir.
    private function pendingRambuPasang()
    {
        return RambuPasang::where('rambu_spk_id', $this->spk->id)
            ->whereIn('status', [StatusRambuPasang::Tertunda->value, StatusRambuPasang::MenungguValidasi->value])
            ->with([
                'rambu.jenisRambu',
                'laporanPengerjaan' => fn ($q) => $q->latest()->with('barangBahan'),
                'kendala' => fn ($q) => $q->latest(),
            ])
            ->get();
    }

    // Every rambu in the SPK, not just the ones awaiting a decision right now —
    // an SPK that's been through a partial reject/redo cycle already has some
    // rambu sitting at Selesai from an earlier accepted round, and admin needs
    // that full picture (not just whatever's newly pending) to make sense of
    // "why is this SPK back in my queue with only one rambu to review".
    private function semuaRambuPasang()
    {
        return RambuPasang::where('rambu_spk_id', $this->spk->id)
            ->with(['rambu.jenisRambu', 'laporanPengerjaan' => fn ($q) => $q->latest()])
            ->get();
    }

    // Kendala items have no "checked" affordance in the UI at all (see
    // show.blade.php), but $checked is still a plain public property —
    // reachable via a direct Livewire method call, not just the rendered
    // checkbox. Called from both entrypoints below (not just lanjutkan())
    // since konfirmasiPenolakan() can in principle be invoked on its own,
    // so a kendala can never be approved as if it were completed work
    // regardless of what a client sends: there's no laporan_pengerjaan
    // behind it to actually validate.
    private function normalisasiCheckedKendala(): void
    {
        foreach ($this->pendingRambuPasang() as $rp) {
            if ($rp->status === StatusRambuPasang::Tertunda) {
                $this->checked[$rp->id] = false;
            }
        }
    }

    public function lanjutkan(): void
    {
        Flux::modal('proses-validasi')->close();

        $this->normalisasiCheckedKendala();

        $uncheckedIds = collect($this->checked)->filter(fn ($v) => ! $v)->keys();

        if ($uncheckedIds->isEmpty()) {
            $this->finalize(approvedIds: collect($this->checked)->keys(), rejections: []);

            return;
        }

        $this->catatanPenolakan = $uncheckedIds->mapWithKeys(fn ($id) => [$id => ''])->toArray();
        $this->deadlineBaru = $this->spk->deadline->toDateString();
        $this->showPenolakanForm = true;
    }

    public function kembali(): void
    {
        $this->showPenolakanForm = false;
    }

    public function konfirmasiPenolakan(): void
    {
        $this->normalisasiCheckedKendala();

        $this->validate([
            'catatanPenolakan.*' => 'required|string|max:1000',
            // Only actually enforced when the admin opted into changing the
            // deadline — deadlineBaru is always pre-filled with the SPK's
            // current deadline (see lanjutkan()) even when ubahDeadline is
            // left unchecked, and that current deadline can legitimately
            // already be today/in the past for an overdue-but-still-Aktif
            // SPK. Validating it unconditionally would block rejecting a
            // rambu on such an SPK entirely unless the admin also pushed
            // its deadline out, which isn't what this checkbox is for.
            'deadlineBaru' => $this->ubahDeadline ? 'required|date|after:today' : 'nullable',
        ], [
            'catatanPenolakan.*.required' => 'Catatan penolakan wajib diisi untuk setiap rambu yang tidak dicentang.',
            'deadlineBaru.after' => 'Deadline baru harus setelah hari ini.',
        ]);

        $approvedIds = collect($this->checked)->filter(fn ($v) => $v)->keys();

        // One outer transaction so a deadline change never lands without the
        // rejection it was granted alongside (or vice versa) — finalize()'s
        // own DB::transaction() nests into this one as a savepoint.
        DB::transaction(function () use ($approvedIds) {
            if ($this->ubahDeadline && $this->deadlineBaru !== $this->spk->deadline->toDateString()) {
                $this->perpanjangDeadline();
            }

            $this->finalize(approvedIds: $approvedIds, rejections: $this->catatanPenolakan);
        });
    }

    // Deliberately separate from finalize()'s per-rambu work: this changes
    // the SPK itself (once), giving the team leeway right at the moment
    // admin is sending work back for revision — the same reasoning
    // PenyesuaianDeadlineSpk uses for automatic pushes, just admin-initiated
    // instead of triggered by a new priority SPK.
    private function perpanjangDeadline(): void
    {
        $deadlineLama = $this->spk->deadline->toDateString();
        $deadlineBaru = Carbon::parse($this->deadlineBaru);

        $this->spk->update([
            'deadline' => $deadlineBaru,
            'deadline_asli' => $deadlineBaru,
            'urgensi' => Spk::computeUrgensi($deadlineBaru, $this->spk->prioritas),
        ]);

        AuditLog::create([
            'user_id' => Auth::id(),
            'spk_id' => $this->spk->id,
            'aksi' => 'deadline_diperpanjang',
            'keterangan' => "Deadline SPK {$this->spk->nomor_surat} diubah dari {$deadlineLama} ke {$this->deadlineBaru} oleh admin saat validasi.",
        ]);

        foreach ($this->spk->dikerjakanOleh as $anggota) {
            Notifikasi::create([
                'user_id' => $anggota->by_user_id,
                'judul' => 'Deadline SPK Diperpanjang',
                'pesan' => "Deadline SPK {$this->spk->nomor_surat} diubah dari {$deadlineLama} ke {$this->deadlineBaru}.",
                'url' => route('user.spk.show', $this->spk),
                'dibaca' => false,
            ]);
        }
    }

    private function finalize($approvedIds, array $rejections): void
    {
        DB::transaction(function () use ($approvedIds, $rejections) {
            foreach ($approvedIds as $rambuPasangId) {
                $rambuPasang = RambuPasang::with(['rambu', 'laporanPengerjaan', 'kendala'])->find($rambuPasangId);

                if (! $rambuPasang) {
                    continue;
                }

                $laporan = $rambuPasang->laporanPengerjaan->first();

                if ($laporan && $laporan->status === StatusLaporan::Diajukan) {
                    $laporan->update([
                        'status' => StatusLaporan::Diterima,
                        'divalidasi_oleh' => Auth::id(),
                        'divalidasi_pada' => now(),
                    ]);
                }

                $rambuPasang->update(['status' => StatusRambuPasang::Selesai]);

                if ($rambuPasang->jenis_pekerjaan === JenisPekerjaan::PasangBaru) {
                    $rambuPasang->rambu->update(['sudah_terpasang' => true]);
                } else {
                    $rambuPasang->rambu->update(['kondisi_terkini' => KondisiRambu::Baik]);
                }

                AuditLog::create([
                    'user_id' => Auth::id(),
                    'spk_id' => $this->spk->id,
                    'aksi' => 'validasi_diterima',
                    'keterangan' => "Laporan untuk {$rambuPasang->rambu->wilayah}, {$rambuPasang->rambu->lokasi} diterima.",
                ]);

                $pelaporId = $laporan?->dilaporkan_oleh ?? $rambuPasang->kendala->first()?->dilaporkan_oleh;

                if ($pelaporId) {
                    Notifikasi::create([
                        'user_id' => $pelaporId,
                        'judul' => 'Laporan Diterima',
                        // Names the specific rambu, not just the SPK — an SPK
                        // with several rambu can have some accepted and some
                        // rejected in the same validasi round, and "SPK X
                        // diterima" alone leaves the petugas no way to tell
                        // which rambu this notification is even about.
                        'pesan' => "Laporan untuk rambu di {$rambuPasang->rambu->wilayah}, {$rambuPasang->rambu->lokasi} (SPK {$this->spk->nomor_surat}) telah diterima.",
                        'url' => route('user.spk.show', $this->spk),
                        'foto' => $laporan?->foto_sesudah ?? $rambuPasang->kendala->first()?->foto,
                        'dibaca' => false,
                    ]);
                }
            }

            foreach ($rejections as $rambuPasangId => $catatan) {
                $rambuPasang = RambuPasang::with(['rambu', 'laporanPengerjaan', 'kendala'])->find($rambuPasangId);

                if (! $rambuPasang) {
                    continue;
                }

                $laporan = $rambuPasang->laporanPengerjaan->first();

                if ($laporan && $laporan->status === StatusLaporan::Diajukan) {
                    $laporan->update([
                        'status' => StatusLaporan::Ditolak,
                        'catatan_penolakan' => $catatan,
                        'divalidasi_oleh' => Auth::id(),
                        'divalidasi_pada' => now(),
                    ]);
                }

                $rambuPasang->update(['status' => StatusRambuPasang::Revisi]);

                AuditLog::create([
                    'user_id' => Auth::id(),
                    'spk_id' => $this->spk->id,
                    'aksi' => 'validasi_ditolak',
                    'keterangan' => "Laporan untuk {$rambuPasang->rambu->wilayah}, {$rambuPasang->rambu->lokasi} ditolak: {$catatan}",
                ]);

                $pelaporId = $laporan?->dilaporkan_oleh ?? $rambuPasang->kendala->first()?->dilaporkan_oleh;

                if ($pelaporId) {
                    Notifikasi::create([
                        'user_id' => $pelaporId,
                        'judul' => 'Laporan Ditolak',
                        'pesan' => "Laporan untuk rambu di {$rambuPasang->rambu->wilayah}, {$rambuPasang->rambu->lokasi} (SPK {$this->spk->nomor_surat}) ditolak: {$catatan}",
                        'url' => route('user.spk.show', $this->spk),
                        'foto' => $laporan?->foto_sesudah ?? $rambuPasang->kendala->first()?->foto,
                        'dibaca' => false,
                    ]);
                }
            }

            // Reset the final-report gate — if anything still needs rework, the
            // perwakilan must re-address it and submit a fresh laporan akhir.
            $this->spk->update(['laporan_akhir_diajukan_at' => null]);
            $this->spk->refresh();

            $semuaSelesai = $this->spk->rambuPasang()
                ->whereNotIn('status', [StatusRambuPasang::Selesai->value, StatusRambuPasang::Batal->value])
                ->doesntExist();

            if ($semuaSelesai) {
                $this->spk->update(['status' => StatusSpk::Selesai, 'selesai_pada' => now()]);
            }
        });

        Flux::toast(variant: 'success', text: 'Validasi berhasil diproses.');

        $this->redirectRoute('admin.validasi.index', navigate: true);
    }

    public function with(): array
    {
        return [
            'pending' => $this->pendingRambuPasang(),
            'semua' => $this->semuaRambuPasang(),
        ];
    }

    public function render()
    {
        return view('pages::admin.validasi.show');
    }
}
