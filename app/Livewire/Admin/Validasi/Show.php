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

    public function lanjutkan(): void
    {
        $uncheckedIds = collect($this->checked)->filter(fn ($v) => ! $v)->keys();

        if ($uncheckedIds->isEmpty()) {
            $this->finalize(approvedIds: collect($this->checked)->keys(), rejections: []);

            return;
        }

        $this->catatanPenolakan = $uncheckedIds->mapWithKeys(fn ($id) => [$id => ''])->toArray();
        $this->showPenolakanForm = true;
    }

    public function kembali(): void
    {
        $this->showPenolakanForm = false;
    }

    public function konfirmasiPenolakan(): void
    {
        $this->validate([
            'catatanPenolakan.*' => 'required|string|max:1000',
        ], [
            'catatanPenolakan.*.required' => 'Catatan penolakan wajib diisi untuk setiap rambu yang tidak dicentang.',
        ]);

        $approvedIds = collect($this->checked)->filter(fn ($v) => $v)->keys();

        $this->finalize(approvedIds: $approvedIds, rejections: $this->catatanPenolakan);
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
                        'pesan' => "Laporan untuk SPK {$this->spk->nomor_surat} telah diterima.",
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
                        'pesan' => "Laporan untuk SPK {$this->spk->nomor_surat} ditolak: {$catatan}",
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
                $this->spk->update(['status' => StatusSpk::Selesai]);
            }
        });

        Flux::toast(variant: 'success', text: 'Validasi berhasil diproses.');

        $this->redirectRoute('admin.validasi.index', navigate: true);
    }

    public function with(): array
    {
        return [
            'pending' => $this->pendingRambuPasang(),
        ];
    }

    public function render()
    {
        return view('pages::admin.validasi.show');
    }
}
