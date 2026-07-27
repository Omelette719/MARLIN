<?php

namespace App\Livewire\Admin\Spk;

use App\Enums\AsalPermintaan;
use App\Enums\JenisPekerjaan;
use App\Enums\Role;
use App\Enums\StatusRambuPasang;
use App\Enums\StatusSpk;
use App\Enums\StatusTindakLanjut;
use App\Enums\Urgensi;
use App\Models\AuditLog;
use App\Models\JenisRambu;
use App\Models\LaporanKondisi;
use App\Models\Notifikasi;
use App\Models\Rambu;
use App\Models\RambuPasang;
use App\Models\RtPerwakilan;
use App\Models\Spk;
use App\Models\User;
use Carbon\Carbon;
use Flux\Flux;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Title('Buat Surat')]
class Create extends Component
{
    use WithFileUploads;

    #[Url(as: 'laporan_kondisi')]
    public ?int $laporanKondisiId = null;

    public string $jenis_spk = 'pasang_baru';

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

    public function mount(): void
    {
        $temuan = $this->laporanKondisiId
            ? LaporanKondisi::with('rambu')->find($this->laporanKondisiId)
            : null;

        if ($temuan) {
            $this->jenis_spk = JenisPekerjaan::Perbaikan->value;
            $this->jalan = $temuan->rambu->jalan ?? '';
            $this->rt = $temuan->rambu->rt ?? '';
            $this->rambuItems[] = [
                'rambu_terdaftar' => true,
                'jenis_rambu_id' => '',
                'lokasi' => '',
                'koordinat' => '',
                'rambu_id' => (string) $temuan->rambu_id,
                'jumlah' => 1,
                'foto_survei' => null,
                'catatan_instruksi' => '',
                'laporan_kondisi_id' => $temuan->id,
            ];
        } else {
            $this->addRambuItem();
        }
    }

    public function addRambuItem(): void
    {
        $this->rambuItems[] = [
            'rambu_terdaftar' => true,
            'jenis_rambu_id' => '',
            'lokasi' => '',
            'koordinat' => '',
            'rambu_id' => '',
            'jumlah' => 1,
            'foto_survei' => null,
            'catatan_instruksi' => '',
            'laporan_kondisi_id' => null,
        ];
    }

    public function removeRambuItem(int $index): void
    {
        unset($this->rambuItems[$index]);
        $this->rambuItems = array_values($this->rambuItems);
    }

    public function render()
    {
        return view('pages::admin.spk.create');
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

    private function generateNomorSurat(): string
    {
        $year = now()->year;
        $sequence = Spk::whereYear('created_at', $year)->count() + 1;

        return sprintf('SR-%d/BJM/%04d', $year, $sequence);
    }

    public function save(): void
    {
        $this->validate([
            'jenis_spk' => 'required|in:pasang_baru,perbaikan',
            'jalan' => 'required|string|max:255',
            'rt' => 'required|string|max:255',
            'kelurahan' => 'required|string|max:255',
            'perihal' => 'nullable|string|max:500',
            'deadline' => 'required|date|after_or_equal:today',
            'asal_permintaan' => 'required|string',
            'keterangan_asal' => 'nullable|string|max:1000',
            'tanggal_survei' => 'nullable|date',
            'file_referensi' => 'nullable|image|max:5120',
            'catatan_pekerja_tambahan' => 'nullable|string|max:2000',
            'rt_nama' => 'nullable|string|max:255',
            'rt_telepon' => 'nullable|string|max:30',
        ]);

        if (count($this->rambuItems) < 1) {
            $this->addError('rambuItems', 'Tambahkan minimal satu rambu.');

            return;
        }

        $isPasangBaru = $this->jenis_spk === JenisPekerjaan::PasangBaru->value;

        foreach ($this->rambuItems as $index => $item) {
            $butuhEntriManual = $isPasangBaru || ! $item['rambu_terdaftar'];

            if ($butuhEntriManual) {
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

        $urgensi = $this->computeUrgensi();
        $nomorSurat = $this->generateNomorSurat();

        $spk = DB::transaction(function () use ($urgensi, $nomorSurat, $isPasangBaru) {
            $spk = Spk::create([
                'nomor_surat' => $nomorSurat,
                'dibuat_oleh' => auth()->id(),
                'jenis_spk' => $this->jenis_spk,
                'jalan' => $this->jalan,
                'rt' => $this->rt,
                'kelurahan' => $this->kelurahan,
                'perihal' => $this->perihal ?: null,
                'deadline' => $this->deadline,
                'prioritas' => $this->prioritas,
                'urgensi' => $urgensi,
                'status' => StatusSpk::Aktif,
                'asal_permintaan' => $this->asal_permintaan,
                'keterangan_asal' => $this->keterangan_asal ?: null,
                'tanggal_survei' => $this->tanggal_survei ?: null,
                'file_referensi' => $this->file_referensi?->store('spk/referensi', 'public'),
                'catatan_pekerja_tambahan' => $this->catatan_pekerja_tambahan ?: null,
            ]);

            if ($this->rt_nama) {
                RtPerwakilan::create([
                    'nama_lengkap' => $this->rt_nama,
                    'no_telepon' => $this->rt_telepon ?: null,
                    'rtperwakilan_spk_id' => $spk->id,
                ]);
            }

            foreach ($this->rambuItems as $item) {
                if ($isPasangBaru) {
                    $rambu = Rambu::create([
                        'jenis_rambu_id' => $item['jenis_rambu_id'],
                        'jalan' => $this->jalan,
                        'rt' => $this->rt,
                        'lokasi' => $item['lokasi'],
                        'koordinat' => $item['koordinat'],
                        'sudah_terpasang' => false,
                    ]);
                } elseif (! $item['rambu_terdaftar']) {
                    // Perbaikan untuk rambu yang secara fisik sudah ada tapi belum
                    // pernah dicatat di sistem. Beda dengan pasang baru: rambu ini
                    // sudah_terpasang sejak awal, dan dianggap rusak (makanya diperbaiki).
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

                RambuPasang::create([
                    'rambu_spk_id' => $spk->id,
                    'rambu_id' => $rambu->id,
                    'laporan_kondisi_id' => $item['laporan_kondisi_id'] ?: null,
                    'jenis_pekerjaan' => $this->jenis_spk,
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
                'user_id' => auth()->id(),
                'spk_id' => $spk->id,
                'aksi' => 'spk_dibuat',
                'keterangan' => "SPK {$spk->nomor_surat} dibuat untuk wilayah {$spk->wilayah}.",
            ]);

            foreach (User::where('role', Role::User)->where('aktif', true)->get() as $petugas) {
                Notifikasi::create([
                    'user_id' => $petugas->id,
                    'judul' => 'SPK Baru Tersedia',
                    'pesan' => "SPK {$spk->nomor_surat} untuk wilayah {$spk->wilayah} sudah bisa dikerjakan.",
                    'dibaca' => false,
                ]);
            }

            return $spk;
        });

        Flux::toast(variant: 'success', text: "Surat {$spk->nomor_surat} berhasil dibuat.");

        $this->redirectRoute('admin.spk.index', navigate: true);
    }
}
