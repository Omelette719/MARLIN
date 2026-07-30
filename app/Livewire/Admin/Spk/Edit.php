<?php

namespace App\Livewire\Admin\Spk;

use App\Enums\AsalPermintaan;
use App\Enums\JenisPekerjaan;
use App\Enums\StatusRambuPasang;
use App\Enums\StatusSpk;
use App\Enums\StatusTindakLanjut;
use App\Enums\Urgensi;
use App\Livewire\Concerns\RejectsNonImageUploads;
use App\Models\AuditLog;
use App\Models\JenisRambu;
use App\Models\LaporanKondisi;
use App\Models\Rambu;
use App\Models\RambuPasang;
use App\Models\RtPerwakilan;
use App\Models\Spk;
use Carbon\Carbon;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Title('Edit Surat')]
class Edit extends Component
{
    use RejectsNonImageUploads;
    use WithFileUploads;

    public Spk $spk;

    public bool $isPasangBaru = false;

    public string $jalan = '';

    public string $rt = '';

    public string $kelurahan = '';

    public string $perihal = '';

    public string $deadline = '';

    public bool $prioritas = false;

    public string $asal_permintaan = '';

    public string $keterangan_asal = '';

    public string $tanggal_survei = '';

    public $file_referensi = null;

    public string $catatan_pekerja_tambahan = '';

    public string $rt_nama = '';

    public string $rt_telepon = '';

    public array $rambuItems = [];

    public ?int $batalIndex = null;

    public string $catatan_pembatalan = '';

    public function mount(Spk $spk): void
    {
        abort_if($spk->status !== StatusSpk::Aktif, 403, 'SPK yang sudah selesai/dibatalkan tidak bisa diedit.');

        $this->spk = $spk;
        $this->isPasangBaru = $spk->jenis_spk === JenisPekerjaan::PasangBaru;
        $this->jalan = $spk->jalan ?? '';
        $this->rt = $spk->rt ?? '';
        $this->kelurahan = $spk->kelurahan ?? '';
        $this->perihal = $spk->perihal ?? '';
        $this->deadline = $spk->deadline->toDateString();
        $this->prioritas = (bool) $spk->prioritas;
        $this->asal_permintaan = $spk->asal_permintaan->value;
        $this->keterangan_asal = $spk->keterangan_asal ?? '';
        $this->tanggal_survei = $spk->tanggal_survei?->toDateString() ?? '';
        $this->catatan_pekerja_tambahan = $spk->catatan_pekerja_tambahan ?? '';

        $rtPerwakilan = $spk->rtPerwakilan()->first();
        $this->rt_nama = $rtPerwakilan?->nama_lengkap ?? '';
        $this->rt_telepon = $rtPerwakilan?->no_telepon ?? '';

        $this->rambuItems = $spk->rambuPasang()->with('rambu')->get()->map(fn (RambuPasang $rp) => [
            'id' => $rp->id,
            'rambu_terdaftar' => true,
            'jenis_rambu_id' => (string) $rp->rambu->jenis_rambu_id,
            'lokasi' => $rp->rambu->lokasi,
            'koordinat' => $rp->rambu->koordinat,
            'rambu_id' => (string) $rp->rambu_id,
            'jumlah' => $rp->jumlah,
            'foto_survei' => null,
            'existing_foto_survei' => $rp->foto_survei,
            'catatan_instruksi' => $rp->catatan_instruksi ?? '',
            'laporan_kondisi_id' => $rp->laporan_kondisi_id,
            'status' => $rp->status->value,
            'catatan_pembatalan' => $rp->catatan_pembatalan,
            'can_hapus' => in_array($rp->status, [StatusRambuPasang::Belum, StatusRambuPasang::Batal], true)
                && ! $rp->kendala()->exists() && ! $rp->laporanPengerjaan()->exists(),
        ])->values()->all();
    }

    public function addRambuItem(): void
    {
        $this->rambuItems[] = [
            'id' => null,
            'rambu_terdaftar' => true,
            'jenis_rambu_id' => '',
            'lokasi' => '',
            'koordinat' => '',
            'rambu_id' => '',
            'jumlah' => 1,
            'foto_survei' => null,
            'existing_foto_survei' => null,
            'catatan_instruksi' => '',
            'laporan_kondisi_id' => null,
            'status' => null,
            'catatan_pembatalan' => null,
            'can_hapus' => false,
        ];
    }

    public function removeRambuItem(int $index): void
    {
        if (! empty($this->rambuItems[$index]['id'])) {
            return;
        }

        unset($this->rambuItems[$index]);
        $this->rambuItems = array_values($this->rambuItems);
    }

    public function bukaBatalkanRambu(int $index): void
    {
        if (empty($this->rambuItems[$index]['id'])) {
            return;
        }

        $this->batalIndex = $index;
        $this->catatan_pembatalan = '';
        $this->resetErrorBag('catatan_pembatalan');
        Flux::modal('batalkan-rambu')->show();
    }

    public function konfirmasiBatalkanRambu(): void
    {
        $this->validate(['catatan_pembatalan' => 'required|string|max:1000']);

        $index = $this->batalIndex;
        $rambuPasangId = $this->rambuItems[$index]['id'] ?? null;

        if (! $rambuPasangId) {
            return;
        }

        DB::transaction(function () use ($rambuPasangId) {
            $rp = RambuPasang::with('rambu')->findOrFail($rambuPasangId);

            $rp->update([
                'status' => StatusRambuPasang::Batal,
                'catatan_pembatalan' => $this->catatan_pembatalan,
            ]);

            AuditLog::create([
                'user_id' => Auth::id(),
                'spk_id' => $this->spk->id,
                'aksi' => 'rambu_pasang_dibatalkan',
                'keterangan' => "Rambu di {$rp->rambu->wilayah}, {$rp->rambu->lokasi} dibatalkan: {$this->catatan_pembatalan}",
            ]);
        });

        $this->rambuItems[$index]['status'] = StatusRambuPasang::Batal->value;
        $this->rambuItems[$index]['catatan_pembatalan'] = $this->catatan_pembatalan;
        $this->rambuItems[$index]['can_hapus'] = true;

        Flux::modal('batalkan-rambu')->close();
        $this->batalIndex = null;

        Flux::toast(variant: 'success', text: 'Rambu berhasil dibatalkan.');
    }

    public function hapusRambu(int $index): void
    {
        $item = $this->rambuItems[$index] ?? null;

        if (! $item || empty($item['id'])) {
            return;
        }

        $rp = RambuPasang::find($item['id']);

        if (! $rp) {
            return;
        }

        $eligible = in_array($rp->status, [StatusRambuPasang::Belum, StatusRambuPasang::Batal], true)
            && ! $rp->kendala()->exists() && ! $rp->laporanPengerjaan()->exists();

        if (! $eligible) {
            Flux::toast(variant: 'danger', text: 'Rambu ini sudah punya progres (kendala/laporan) — batalkan saja, tidak bisa dihapus.');

            return;
        }

        $rp->loadMissing('rambu');

        DB::transaction(function () use ($rp) {
            AuditLog::create([
                'user_id' => Auth::id(),
                'spk_id' => $this->spk->id,
                'aksi' => 'rambu_pasang_dihapus',
                'keterangan' => "Rambu di {$rp->rambu->wilayah}, {$rp->rambu->lokasi} dihapus dari SPK {$this->spk->nomor_surat}.",
            ]);

            $rp->delete();
        });

        unset($this->rambuItems[$index]);
        $this->rambuItems = array_values($this->rambuItems);

        Flux::toast(variant: 'success', text: 'Rambu berhasil dihapus dari surat.');
    }

    private function computeUrgensi(): Urgensi
    {
        if ($this->prioritas) {
            return Urgensi::Tinggi;
        }

        $daysUntilDeadline = now()->startOfDay()->diffInDays(Carbon::parse($this->deadline)->startOfDay(), false);

        return match (true) {
            $daysUntilDeadline <= 2 => Urgensi::Tinggi,
            $daysUntilDeadline <= 7 => Urgensi::Sedang,
            default => Urgensi::Rendah,
        };
    }

    public function save(): void
    {
        $this->validate([
            'jalan' => 'required|string|max:255',
            'rt' => 'required|string|max:255',
            'kelurahan' => 'required|string|max:255',
            'perihal' => 'nullable|string|max:500',
            'deadline' => 'required|date',
            'asal_permintaan' => 'required|string',
            'keterangan_asal' => 'nullable|string|max:1000',
            'tanggal_survei' => 'nullable|date',
            'file_referensi' => 'nullable|image|max:5120',
            'catatan_pekerja_tambahan' => 'nullable|string|max:2000',
            'rt_nama' => 'nullable|string|max:255',
            'rt_telepon' => 'nullable|string|max:30',
        ]);

        if (count($this->rambuItems) < 1) {
            $this->addError('rambuItems', 'Minimal harus ada satu rambu.');

            return;
        }

        foreach ($this->rambuItems as $index => $item) {
            $manual = $this->isPasangBaru || ! $item['rambu_terdaftar'];

            if ($manual) {
                $this->validate([
                    "rambuItems.$index.jenis_rambu_id" => 'required|exists:jenis_rambu,id',
                    "rambuItems.$index.lokasi" => 'required|string|max:255',
                    "rambuItems.$index.koordinat" => 'required|string|max:255',
                ]);
            } else {
                $this->validate([
                    "rambuItems.$index.rambu_id" => 'required|exists:rambu,id',
                ]);
            }

            $this->validate([
                "rambuItems.$index.jumlah" => 'required|integer|min:1',
                "rambuItems.$index.foto_survei" => 'nullable|image|max:5120',
            ]);
        }

        DB::transaction(function () {
            $this->spk->update([
                'jalan' => $this->jalan,
                'rt' => $this->rt,
                'kelurahan' => $this->kelurahan,
                'wilayah' => Spk::composeWilayah($this->jalan, $this->rt, $this->kelurahan),
                'perihal' => $this->perihal ?: null,
                'deadline' => $this->deadline,
                'prioritas' => $this->prioritas,
                'urgensi' => $this->computeUrgensi(),
                'asal_permintaan' => $this->asal_permintaan,
                'keterangan_asal' => $this->keterangan_asal ?: null,
                'tanggal_survei' => $this->tanggal_survei ?: null,
                'file_referensi' => $this->file_referensi ? $this->file_referensi->store('spk/referensi', 'public') : $this->spk->file_referensi,
                'catatan_pekerja_tambahan' => $this->catatan_pekerja_tambahan ?: null,
            ]);

            if ($this->rt_nama) {
                RtPerwakilan::updateOrCreate(
                    ['rtperwakilan_spk_id' => $this->spk->id],
                    ['nama_lengkap' => $this->rt_nama, 'no_telepon' => $this->rt_telepon ?: null]
                );
            }

            foreach ($this->rambuItems as $item) {
                $manual = $this->isPasangBaru || ! $item['rambu_terdaftar'];

                if ($item['id']) {
                    $rambuPasang = RambuPasang::with('rambu')->findOrFail($item['id']);

                    if ($manual) {
                        $rambuPasang->rambu->update([
                            'jenis_rambu_id' => $item['jenis_rambu_id'],
                            'lokasi' => $item['lokasi'],
                            'koordinat' => $item['koordinat'],
                        ]);
                    } else {
                        $rambuPasang->rambu_id = (int) $item['rambu_id'];
                    }

                    $rambuPasang->jumlah = $item['jumlah'];
                    $rambuPasang->catatan_instruksi = $item['catatan_instruksi'] ?: null;

                    if ($item['foto_survei']) {
                        $rambuPasang->foto_survei = $item['foto_survei']->store('rambu-pasang/survei', 'public');
                    }

                    $rambuPasang->save();

                    continue;
                }

                if ($this->isPasangBaru) {
                    $rambu = Rambu::create([
                        'jenis_rambu_id' => $item['jenis_rambu_id'],
                        'jalan' => $this->jalan,
                        'rt' => $this->rt,
                        'lokasi' => $item['lokasi'],
                        'koordinat' => $item['koordinat'],
                        'sudah_terpasang' => false,
                    ]);
                } elseif (! $item['rambu_terdaftar']) {
                    $rambu = Rambu::create([
                        'jenis_rambu_id' => $item['jenis_rambu_id'],
                        'jalan' => $this->jalan,
                        'rt' => $this->rt,
                        'lokasi' => $item['lokasi'],
                        'koordinat' => $item['koordinat'],
                        'kondisi_terkini' => 'rusak',
                        'sudah_terpasang' => true,
                    ]);
                } else {
                    $rambu = Rambu::findOrFail($item['rambu_id']);
                }

                $rambuPasang = RambuPasang::create([
                    'rambu_spk_id' => $this->spk->id,
                    'rambu_id' => $rambu->id,
                    'laporan_kondisi_id' => $item['laporan_kondisi_id'] ?: null,
                    'jenis_pekerjaan' => $this->spk->jenis_spk,
                    'jumlah' => $item['jumlah'],
                    'foto_survei' => $item['foto_survei'] ? $item['foto_survei']->store('rambu-pasang/survei', 'public') : null,
                    'catatan_instruksi' => $item['catatan_instruksi'] ?: null,
                    'status' => StatusRambuPasang::Belum,
                ]);

                if (! empty($item['laporan_kondisi_id'])) {
                    LaporanKondisi::where('id', $item['laporan_kondisi_id'])
                        ->update(['status_tindak_lanjut' => StatusTindakLanjut::SudahDibuatkanSpk]);
                }
            }

            AuditLog::create([
                'user_id' => Auth::id(),
                'spk_id' => $this->spk->id,
                'aksi' => 'spk_diedit',
                'keterangan' => "SPK {$this->spk->nomor_surat} diperbarui.",
            ]);
        });

        Flux::toast(variant: 'success', text: "Surat {$this->spk->nomor_surat} berhasil diperbarui.");

        $this->redirectRoute('admin.spk.show', $this->spk, navigate: true);
    }

    public function with(): array
    {
        $jenisRambuOptions = JenisRambu::orderBy('nama_jenis')->get();
        $rambuOptions = Rambu::with('jenisRambu')
            ->orderByRaw("CASE WHEN kondisi_terkini = 'rusak' THEN 0 ELSE 1 END")
            ->orderBy('wilayah')
            ->get();

        return [
            'asalPermintaanOptions' => AsalPermintaan::cases(),
            'jenisRambuOptions' => $jenisRambuOptions,
            'jenisRambuSelectOptions' => $jenisRambuOptions->map(fn ($jr) => [
                'value' => (string) $jr->id,
                'label' => $jr->nama_jenis,
            ])->values(),
            'rambuOptions' => $rambuOptions,
            'rambuSelectOptions' => $rambuOptions->map(fn ($r) => [
                'value' => (string) $r->id,
                'label' => "{$r->wilayah}, {$r->lokasi} ({$r->jenisRambu?->nama_jenis})".($r->kondisi_terkini->value === 'rusak' ? ', RUSAK' : ''),
            ])->values(),
        ];
    }

    public function render()
    {
        return view('pages::admin.spk.edit');
    }
}
