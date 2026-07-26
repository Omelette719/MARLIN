<?php

namespace App\Livewire\Admin\Spk;

use App\Enums\AsalPermintaan;
use App\Enums\StatusSpk;
use App\Enums\Urgensi;
use App\Models\AuditLog;
use App\Models\RtPerwakilan;
use App\Models\Spk;
use Carbon\Carbon;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Title('Edit Surat')]
class Edit extends Component
{
    use WithFileUploads;

    public Spk $spk;

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

    public function mount(Spk $spk): void
    {
        abort_if($spk->status !== StatusSpk::Aktif, 403, 'SPK yang sudah selesai/dibatalkan tidak bisa diedit.');

        $this->spk = $spk;
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
            'file_referensi' => 'nullable|file|max:5120',
            'catatan_pekerja_tambahan' => 'nullable|string|max:2000',
            'rt_nama' => 'nullable|string|max:255',
            'rt_telepon' => 'nullable|string|max:30',
        ]);

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

        AuditLog::create([
            'user_id' => Auth::id(),
            'spk_id' => $this->spk->id,
            'aksi' => 'spk_diedit',
            'keterangan' => "SPK {$this->spk->nomor_surat} diperbarui.",
        ]);

        Flux::toast(variant: 'success', text: "Surat {$this->spk->nomor_surat} berhasil diperbarui.");

        $this->redirectRoute('admin.spk.show', $this->spk, navigate: true);
    }

    public function with(): array
    {
        return [
            'asalPermintaanOptions' => AsalPermintaan::cases(),
        ];
    }

    public function render()
    {
        return view('pages::admin.spk.edit');
    }
}
