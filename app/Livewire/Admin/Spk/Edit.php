<?php

namespace App\Livewire\Admin\Spk;

use App\Enums\AsalPermintaan;
use App\Enums\JenisPekerjaan;
use App\Enums\StatusRambuPasang;
use App\Enums\StatusSpk;
use App\Enums\StatusTindakLanjut;
use App\Livewire\Concerns\RejectsNonImageUploads;
use App\Models\AuditLog;
use App\Models\JenisRambu;
use App\Models\LaporanKondisi;
use App\Models\Notifikasi;
use App\Models\Rambu;
use App\Models\RambuPasang;
use App\Models\RtPerwakilan;
use App\Models\Spk;
use App\Rules\Koordinat;
use App\Support\PenyesuaianDeadlineSpk;
use App\Support\WilayahBanjarmasin;
use Carbon\Carbon;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Title('Edit Surat')]
class Edit extends Component
{
    use RejectsNonImageUploads {
        RejectsNonImageUploads::updated as rejectNonImageUpload;
    }
    use WithFileUploads;

    public Spk $spk;

    // A rambu row stops being safe to edit once it has left the "not yet
    // decided" state: Tertunda/MenungguValidasi are actively sitting in (or
    // waiting to enter) the admin validation queue, and Selesai/Batal are
    // already final. Editing jenis_pekerjaan/rambu/lokasi/koordinat out from
    // under a row in any of those states would silently desync it from the
    // laporan_pengerjaan/kendala already filed against it — see the "still
    // editable while pending validasi" bug this guard closes.
    private const EDITABLE_STATUSES = [StatusRambuPasang::Belum, StatusRambuPasang::Urgent, StatusRambuPasang::Revisi];

    public string $jalan = '';

    public string $rt = '';

    public string $kelurahan = '';

    public string $perihal = '';

    public string $deadline = '';

    public bool $prioritas = false;

    public string $asal_permintaan = '';

    public string $keterangan_asal = '';

    public string $tanggal_survei = '';

    public string $petugas_survei = '';

    public $file_referensi = null;

    public string $catatan_pekerja_tambahan = '';

    public string $rt_nama = '';

    public string $rt_telepon = '';

    public array $rambuItems = [];

    public array $koordinatWarnings = [];

    public ?int $batalIndex = null;

    public string $catatan_pembatalan = '';

    // file_referensi is a scanned request letter, not a field photo — allow PDF too.
    protected function pdfAllowedFields(): array
    {
        return ['file_referensi'];
    }

    // Without this, Laravel's auto-generated message for a nested array rule
    // like "rambuItems.0.jenis_rambu_id" reads as the raw dotted path ("The
    // rambu items.0.jenis rambu id field is required.") instead of the
    // field's own visible label. Livewire merges this into every validate()
    // call in the component automatically.
    protected function validationAttributes(): array
    {
        return [
            'rambuItems.*.jenis_pekerjaan' => 'Jenis Pekerjaan',
            'rambuItems.*.jenis_rambu_id' => 'Jenis Rambu',
            'rambuItems.*.lokasi' => 'Lokasi',
            'rambuItems.*.koordinat' => 'Koordinat',
            'rambuItems.*.rambu_id' => 'Rambu',
            'rambuItems.*.jumlah' => 'Jumlah',
            'rambuItems.*.foto_survei' => 'Foto Tempat',
            'catatan_pembatalan' => 'Catatan Pembatalan',
        ];
    }

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
        $this->petugas_survei = $spk->petugas_survei ?? '';
        $this->catatan_pekerja_tambahan = $spk->catatan_pekerja_tambahan ?? '';

        $rtPerwakilan = $spk->rtPerwakilan()->first();
        $this->rt_nama = $rtPerwakilan?->nama_lengkap ?? '';
        $this->rt_telepon = $rtPerwakilan?->no_telepon ?? '';

        $this->rambuItems = $spk->rambuPasang()->with('rambu.jenisRambu')->get()->map(fn (RambuPasang $rp) => [
            'id' => $rp->id,
            'jenis_pekerjaan' => $rp->jenis_pekerjaan->value,
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
            'can_edit' => in_array($rp->status, self::EDITABLE_STATUSES, true),
            // Only used for the read-only summary shown when can_edit is
            // false — the editable form below builds its own label from
            // jenisRambuSelectOptions/rambuSelectOptions instead.
            'rambu_label' => "{$rp->rambu->jenisRambu?->nama_jenis}: {$rp->rambu->wilayah}, {$rp->rambu->lokasi}",
        ])->values()->all();
    }

    public function addRambuItem(): void
    {
        $this->rambuItems[] = [
            'id' => null,
            'jenis_pekerjaan' => JenisPekerjaan::PasangBaru->value,
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
            'can_edit' => true,
        ];
    }

    public function removeRambuItem(int $index): void
    {
        if (! empty($this->rambuItems[$index]['id'])) {
            return;
        }

        unset($this->rambuItems[$index]);
        unset($this->koordinatWarnings[$index]);
        $this->rambuItems = array_values($this->rambuItems);
    }

    // Fields with format rules (regex/date-comparison) that are worth
    // catching before the admin has finished editing the whole form — same
    // reasoning as the koordinat warning below.
    private const LIVE_VALIDATED_FIELDS = ['rt', 'rt_nama', 'rt_telepon', 'petugas_survei', 'deadline', 'tanggal_survei'];

    public function updated(string $property, mixed $value = null): void
    {
        $this->rejectNonImageUpload($property, $value);

        if (preg_match('/^rambuItems\.(\d+)\.koordinat$/', $property, $m)) {
            $index = (int) $m[1];

            $this->validateOnly($property, [
                $property => ['nullable', 'string', 'max:255', new Koordinat],
            ]);

            $this->refreshKoordinatWarning($index);

            return;
        }

        if (in_array($property, self::LIVE_VALIDATED_FIELDS, true)) {
            $this->validateOnly($property, $this->headerRules(), $this->headerMessages());
        }
    }

    // See Create::refreshKoordinatWarning() for the rationale — advisory,
    // not blocking, since close-together signs can be legitimate.
    private function refreshKoordinatWarning(int $index): void
    {
        $koordinat = $this->rambuItems[$index]['koordinat'] ?? '';
        $coords = Rambu::parseKoordinat($koordinat);

        if (! $coords) {
            unset($this->koordinatWarnings[$index]);

            return;
        }

        $kecualikanId = ! empty($this->rambuItems[$index]['rambu_id']) ? (int) $this->rambuItems[$index]['rambu_id'] : null;

        $peringatan = Rambu::terdekat($koordinat, kecualikanId: $kecualikanId)
            ->map(fn (Rambu $r) => [
                'label' => "{$r->jenisRambu?->nama_jenis}: {$r->wilayah}, {$r->lokasi}",
                'jarak' => round($r->jarak_meter),
            ])->all();

        foreach ($this->rambuItems as $i => $other) {
            if ($i === $index || empty($other['koordinat'])) {
                continue;
            }

            $otherCoords = Rambu::parseKoordinat($other['koordinat']);

            if (! $otherCoords) {
                continue;
            }

            $jarak = Rambu::jarakMeter($coords[0], $coords[1], $otherCoords[0], $otherCoords[1]);

            if ($jarak <= 20) {
                $peringatan[] = ['label' => 'Rambu #'.($i + 1).' di formulir ini', 'jarak' => round($jarak)];
            }
        }

        if (empty($peringatan)) {
            unset($this->koordinatWarnings[$index]);

            return;
        }

        $this->koordinatWarnings[$index] = $peringatan;
    }

    public function bukaBatalkanRambu(int $index): void
    {
        if (empty($this->rambuItems[$index]['id']) || ! $this->rambuItems[$index]['can_edit']) {
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

        // Re-checked fresh from the DB, not the possibly-stale local
        // rambuItems array — this form can sit open long enough for the
        // rambu to have moved into/out of validation in the meantime (e.g.
        // the petugas submitted a laporan, or another admin already
        // validated it), and the local can_edit flag was only ever
        // accurate as of when the page loaded.
        $rp = RambuPasang::with('rambu')->find($rambuPasangId);

        if (! $rp || ! in_array($rp->status, self::EDITABLE_STATUSES, true)) {
            Flux::modal('batalkan-rambu')->close();
            Flux::toast(variant: 'danger', text: 'Rambu ini sudah berubah status (mis. sedang divalidasi atau sudah selesai), muat ulang halaman untuk melihat kondisi terkininya.');

            return;
        }

        DB::transaction(function () use ($rp) {
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

            foreach ($this->spk->petugas as $petugas) {
                Notifikasi::create([
                    'user_id' => $petugas->id,
                    'judul' => 'Rambu Dibatalkan',
                    'pesan' => "Rambu di {$rp->rambu->wilayah}, {$rp->rambu->lokasi} (SPK {$this->spk->nomor_surat}) dibatalkan: {$this->catatan_pembatalan}",
                    'url' => route('user.spk.show', $this->spk),
                    'dibaca' => false,
                ]);
            }
        });

        $this->rambuItems[$index]['status'] = StatusRambuPasang::Batal->value;
        $this->rambuItems[$index]['catatan_pembatalan'] = $this->catatan_pembatalan;
        $this->rambuItems[$index]['can_hapus'] = true;
        $this->rambuItems[$index]['can_edit'] = false;

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
            Flux::toast(variant: 'danger', text: 'Rambu ini sudah punya progres (kendala/laporan), batalkan saja, tidak bisa dihapus.');

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

        Flux::modal("hapus-rambu-{$index}")->close();
        Flux::toast(variant: 'success', text: 'Rambu berhasil dihapus dari surat.');
    }

    // Shared with updated()'s live per-field validation, so the rules a
    // field is held to are identical whether it's checked while typing or
    // at final submit — one definition, never two copies to drift apart.
    private function headerRules(): array
    {
        return [
            'jalan' => 'required|string|max:255',
            // Nullable, not required: not every location sits inside a clear
            // RT/kelurahan (e.g. a spot along a toll road or highway KM
            // marker), and ComposesWilayah already composes wilayah from
            // whichever of jalan/rt/kelurahan are actually filled in.
            'rt' => ['nullable', 'string', 'max:255', 'regex:/^[0-9]+$/'],
            'kelurahan' => ['nullable', 'string', Rule::in(WilayahBanjarmasin::kelurahanOptions())],
            'perihal' => 'nullable|string|max:500',
            'deadline' => 'required|date|after:today',
            'asal_permintaan' => ['required', Rule::enum(AsalPermintaan::class)],
            'keterangan_asal' => 'nullable|string|max:1000',
            'tanggal_survei' => 'nullable|date|before_or_equal:today',
            'petugas_survei' => ['nullable', 'string', 'max:500', 'required_with:tanggal_survei', 'regex:/^[a-zA-Z\s,]+$/'],
            'file_referensi' => 'nullable|mimes:jpg,jpeg,png,gif,webp,pdf|max:5120',
            'catatan_pekerja_tambahan' => 'nullable|string|max:2000',
            'rt_nama' => ['nullable', 'string', 'max:255', 'regex:/^[a-zA-Z\s]+$/'],
            'rt_telepon' => ['nullable', 'string', 'max:30', 'regex:/^[0-9]+$/'],
        ];
    }

    private function headerMessages(): array
    {
        return [
            'rt.regex' => 'RT hanya boleh berisi angka.',
            'deadline.after' => 'Deadline harus setelah hari ini.',
            'tanggal_survei.before_or_equal' => 'Tanggal survei tidak boleh di masa depan.',
            'petugas_survei.required_with' => 'Isi petugas survei kalau tanggal survei sudah diisi.',
            'petugas_survei.regex' => 'Petugas survei hanya boleh berisi huruf, spasi, dan koma (untuk memisahkan nama).',
            'rt_nama.regex' => 'Nama Contact Person hanya boleh berisi huruf dan spasi, tanpa angka atau simbol.',
            'rt_telepon.regex' => 'Nomor telepon hanya boleh berisi angka.',
        ];
    }

    public function save(): void
    {
        $this->validate($this->headerRules(), $this->headerMessages());

        if (count($this->rambuItems) < 1) {
            $this->addError('rambuItems', 'Minimal harus ada satu rambu.');

            return;
        }

        foreach ($this->rambuItems as $index => $item) {
            $this->validate([
                "rambuItems.$index.jenis_pekerjaan" => 'required|in:pasang_baru,perbaikan',
            ]);

            $manual = $item['jenis_pekerjaan'] === JenisPekerjaan::PasangBaru->value || ! $item['rambu_terdaftar'];

            if ($manual) {
                $this->validate([
                    "rambuItems.$index.jenis_rambu_id" => 'required|exists:jenis_rambu,id',
                    "rambuItems.$index.lokasi" => 'required|string|max:255',
                    "rambuItems.$index.koordinat" => ['required', 'string', 'max:255', new Koordinat],
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

        $rambuIdDipilihGanda = collect($this->rambuItems)
            ->pluck('rambu_id')
            ->filter()
            ->duplicates();

        if ($rambuIdDipilihGanda->isNotEmpty()) {
            $this->addError('rambuItems', 'Ada rambu existing yang sama dipilih lebih dari sekali dalam surat ini.');

            return;
        }

        DB::transaction(function () {
            $this->spk->update([
                'jalan' => $this->jalan,
                'rt' => $this->rt,
                'kelurahan' => $this->kelurahan,
                'wilayah' => Spk::composeWilayah($this->jalan, $this->rt, $this->kelurahan),
                'perihal' => $this->perihal ?: null,
                'deadline' => $this->deadline,
                'deadline_asli' => $this->deadline,
                'prioritas' => $this->prioritas,
                'urgensi' => Spk::computeUrgensi(Carbon::parse($this->deadline), $this->prioritas),
                'asal_permintaan' => $this->asal_permintaan,
                'keterangan_asal' => $this->keterangan_asal ?: null,
                'tanggal_survei' => $this->tanggal_survei ?: null,
                'petugas_survei' => $this->petugas_survei ?: null,
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
                $isPasangBaruItem = $item['jenis_pekerjaan'] === JenisPekerjaan::PasangBaru->value;
                $manual = $isPasangBaruItem || ! $item['rambu_terdaftar'];

                if ($item['id']) {
                    $rambuPasang = RambuPasang::with('rambu')->findOrFail($item['id']);

                    // Re-checked fresh from the DB rather than trusting the
                    // request's can_edit flag — the same staleness window as
                    // konfirmasiBatalkanRambu() above applies here too, and
                    // this is the mutation that actually corrupts data if it
                    // ever fires against a row mid-validasi or already done.
                    if (! in_array($rambuPasang->status, self::EDITABLE_STATUSES, true)) {
                        continue;
                    }

                    if ($manual) {
                        $rambuPasang->rambu->update([
                            'jenis_rambu_id' => $item['jenis_rambu_id'],
                            'lokasi' => $item['lokasi'],
                            'koordinat' => $item['koordinat'],
                        ]);
                    } else {
                        $rambuPasang->rambu_id = (int) $item['rambu_id'];
                    }

                    $rambuPasang->jenis_pekerjaan = $item['jenis_pekerjaan'];
                    $rambuPasang->jumlah = $item['jumlah'];
                    $rambuPasang->catatan_instruksi = $item['catatan_instruksi'] ?: null;

                    if ($item['foto_survei']) {
                        $rambuPasang->foto_survei = $item['foto_survei']->store('rambu-pasang/survei', 'public');
                    }

                    $rambuPasang->save();

                    continue;
                }

                if ($isPasangBaruItem) {
                    $rambu = Rambu::create([
                        'jenis_rambu_id' => $item['jenis_rambu_id'],
                        'jalan' => $this->jalan,
                        'rt' => $this->rt,
                        'kelurahan' => $this->kelurahan,
                        'lokasi' => $item['lokasi'],
                        'koordinat' => $item['koordinat'],
                        'sudah_terpasang' => false,
                    ]);
                } elseif (! $item['rambu_terdaftar']) {
                    $rambu = Rambu::create([
                        'jenis_rambu_id' => $item['jenis_rambu_id'],
                        'jalan' => $this->jalan,
                        'rt' => $this->rt,
                        'kelurahan' => $this->kelurahan,
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
                    'jenis_pekerjaan' => $item['jenis_pekerjaan'],
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

            PenyesuaianDeadlineSpk::terapkan($this->spk);
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
            'kelurahanSelectOptions' => collect(WilayahBanjarmasin::kelurahanOptions())->map(fn ($k) => [
                'value' => $k,
                'label' => $k,
            ])->values(),
        ];
    }

    public function render()
    {
        return view('pages::admin.spk.edit');
    }
}
