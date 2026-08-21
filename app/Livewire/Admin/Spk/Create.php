<?php

namespace App\Livewire\Admin\Spk;

use App\Enums\AsalPermintaan;
use App\Enums\JenisPekerjaan;
use App\Enums\Role;
use App\Enums\StatusRambuPasang;
use App\Enums\StatusSpk;
use App\Enums\StatusTindakLanjut;
use App\Livewire\Concerns\RejectsNonImageUploads;
use App\Models\AuditLog;
use App\Models\ContactPerson;
use App\Models\JenisRambu;
use App\Models\LaporanKondisi;
use App\Models\Notifikasi;
use App\Models\Rambu;
use App\Models\RambuPasang;
use App\Models\Spk;
use App\Models\User;
use App\Rules\Koordinat;
use App\Support\PenyesuaianDeadlineSpk;
use App\Support\WilayahBanjarmasin;
use Carbon\Carbon;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Title('Buat Surat')]
class Create extends Component
{
    use RejectsNonImageUploads {
        RejectsNonImageUploads::updated as rejectNonImageUpload;
    }
    use WithFileUploads;

    #[Url(as: 'laporan_kondisi')]
    public ?int $laporanKondisiId = null;

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

    public string $contact_person_nama = '';

    public string $contact_person_telepon = '';

    public array $rambuItems = [];

    public array $koordinatWarnings = [];

    // file_referensi is a scanned request letter, not a field photo — allow PDF too.
    protected function pdfAllowedFields(): array
    {
        return ['file_referensi'];
    }

    public function mount(): void
    {
        $temuan = $this->laporanKondisiId
            ? LaporanKondisi::with('rambu')->find($this->laporanKondisiId)
            : null;

        if ($temuan) {
            $this->jalan = $temuan->rambu->jalan ?? '';
            $this->rt = $temuan->rambu->rt ?? '';
            $this->rambuItems[] = [
                'jenis_pekerjaan' => JenisPekerjaan::Perbaikan->value,
                'rambu_terdaftar' => true,
                'jenis_rambu_id' => '',
                'lokasi' => '',
                'koordinat' => '',
                'rambu_id' => (string) $temuan->rambu_id,
                'jumlah' => 1,
                'foto_survei' => null,
                // The temuan already has photo evidence of what needs fixing —
                // shown as a preview and copied into this rambu_pasang's own
                // foto_survei at submit() unless the admin uploads a different
                // photo instead (rambuItems.*.foto_survei above takes priority).
                'foto_survei_existing' => $temuan->foto,
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
            'jenis_pekerjaan' => JenisPekerjaan::PasangBaru->value,
            'rambu_terdaftar' => true,
            'jenis_rambu_id' => '',
            'lokasi' => '',
            'koordinat' => '',
            'rambu_id' => '',
            'jumlah' => 1,
            'foto_survei' => null,
            'foto_survei_existing' => null,
            'catatan_instruksi' => '',
            'laporan_kondisi_id' => null,
        ];
    }

    public function removeRambuItem(int $index): void
    {
        unset($this->rambuItems[$index]);
        unset($this->koordinatWarnings[$index]);
        $this->rambuItems = array_values($this->rambuItems);
    }

    // Fields with format rules (regex/date-comparison) that are worth
    // catching before the admin has filled out the whole form — same
    // reasoning as the koordinat warning below: don't make them discover a
    // typo only after clicking Simpan Surat at the very end.
    private const LIVE_VALIDATED_FIELDS = ['rt', 'contact_person_nama', 'contact_person_telepon', 'petugas_survei', 'deadline', 'tanggal_survei'];

    public function updated(string $property, mixed $value = null): void
    {
        $this->rejectNonImageUpload($property, $value);

        if (preg_match('/^rambuItems\.(\d+)\.koordinat$/', $property, $m)) {
            $this->validateOnly($property, [
                $property => ['nullable', 'string', 'max:255', new Koordinat],
            ]);

            $this->refreshKoordinatWarning((int) $m[1]);

            return;
        }

        if (in_array($property, self::LIVE_VALIDATED_FIELDS, true)) {
            $this->validateOnly($property, $this->headerRules(), $this->headerMessages());
        }
    }

    // Warns (without blocking) about rambu already registered near this
    // coordinate — either in the database, or another item on this same
    // form — so the admin doesn't accidentally register a sign twice.
    // Genuinely close-together signs (e.g. two different signs on one pole)
    // are legitimate, so this stays advisory rather than a hard error.
    private function refreshKoordinatWarning(int $index): void
    {
        $koordinat = $this->rambuItems[$index]['koordinat'] ?? '';
        $coords = Rambu::parseKoordinat($koordinat);

        if (! $coords) {
            unset($this->koordinatWarnings[$index]);

            return;
        }

        $peringatan = Rambu::terdekat($koordinat)
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
            'kelurahanSelectOptions' => collect(WilayahBanjarmasin::kelurahanOptions())->map(fn ($k) => [
                'value' => $k,
                'label' => $k,
            ])->values(),
        ];
    }

    private function generateNomorSurat(): string
    {
        $year = now()->year;
        $sequence = Spk::whereYear('created_at', $year)->count() + 1;

        return sprintf('SR-%d/BJM/%04d', $year, $sequence);
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
        ];
    }

    // A freshly-uploaded photo always wins. Otherwise, if this rambu item
    // came from a temuan report, copy that report's photo into its own file
    // under rambu-pasang/survei so it has independent storage from
    // laporan_kondisi.foto (e.g. surviving if the temuan row's photo is ever
    // replaced) instead of just reusing the same stored path.
    private function resolveFotoSurvei(array $item): ?string
    {
        if ($item['foto_survei']) {
            return $item['foto_survei']->store('rambu-pasang/survei', 'public');
        }

        if (empty($item['foto_survei_existing'])) {
            return null;
        }

        $sumber = $item['foto_survei_existing'];
        $tujuan = 'rambu-pasang/survei/'.Str::random(40).'.'.pathinfo($sumber, PATHINFO_EXTENSION);

        return Storage::disk('public')->copy($sumber, $tujuan) ? $tujuan : null;
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
            'contact_person_nama' => ['nullable', 'string', 'max:255', 'regex:/^[a-zA-Z\s]+$/'],
            'contact_person_telepon' => ['nullable', 'string', 'max:30', 'regex:/^[0-9]+$/'],
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
            'contact_person_nama.regex' => 'Nama Contact Person hanya boleh berisi huruf dan spasi, tanpa angka atau simbol.',
            'contact_person_telepon.regex' => 'Nomor telepon hanya boleh berisi angka.',
        ];
    }

    public function save(): void
    {
        try {
            $this->validate($this->headerRules(), $this->headerMessages());

            if (count($this->rambuItems) < 1) {
                $this->addError('rambuItems', 'Tambahkan minimal satu rambu.');

                return;
            }

            foreach ($this->rambuItems as $index => $item) {
                $this->validate([
                    "rambuItems.$index.jenis_pekerjaan" => 'required|in:pasang_baru,perbaikan',
                ]);

                $isPasangBaruItem = $item['jenis_pekerjaan'] === JenisPekerjaan::PasangBaru->value;
                $butuhEntriManual = $isPasangBaruItem || ! $item['rambu_terdaftar'];

                if ($butuhEntriManual) {
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
                    // Only actually optional when a temuan already supplied a
                    // photo (foto_survei_existing) — a fresh rambu row still
                    // needs visual proof of the site before it's assigned out.
                    "rambuItems.$index.foto_survei" => [
                        empty($item['foto_survei_existing']) ? 'required' : 'nullable',
                        'image',
                        'max:5120',
                    ],
                ]);
            }
        } catch (ValidationException $e) {
            Flux::toast(variant: 'danger', text: 'Harap isi field yang diperlukan.');

            throw $e;
        }

        $rambuIdDipilihGanda = collect($this->rambuItems)
            ->pluck('rambu_id')
            ->filter()
            ->duplicates();

        if ($rambuIdDipilihGanda->isNotEmpty()) {
            $this->addError('rambuItems', 'Ada rambu existing yang sama dipilih lebih dari sekali dalam surat ini.');
            Flux::toast(variant: 'danger', text: 'Ada rambu existing yang sama dipilih lebih dari sekali dalam surat ini.');

            return;
        }

        $urgensi = Spk::computeUrgensi(Carbon::parse($this->deadline), $this->prioritas);
        $nomorSurat = $this->generateNomorSurat();

        $spk = DB::transaction(function () use ($urgensi, $nomorSurat) {
            $spk = Spk::create([
                'nomor_surat' => $nomorSurat,
                'dibuat_oleh' => Auth::id(),
                'jalan' => $this->jalan,
                'rt' => $this->rt,
                'kelurahan' => $this->kelurahan,
                'perihal' => $this->perihal ?: null,
                'deadline' => $this->deadline,
                'deadline_asli' => $this->deadline,
                'prioritas' => $this->prioritas,
                'urgensi' => $urgensi,
                'status' => StatusSpk::Aktif,
                'asal_permintaan' => $this->asal_permintaan,
                'keterangan_asal' => $this->keterangan_asal ?: null,
                'tanggal_survei' => $this->tanggal_survei ?: null,
                'petugas_survei' => $this->petugas_survei ?: null,
                'file_referensi' => $this->file_referensi?->store('spk/referensi', 'public'),
                'catatan_pekerja_tambahan' => $this->catatan_pekerja_tambahan ?: null,
            ]);

            if ($this->contact_person_nama) {
                ContactPerson::create([
                    'nama_lengkap' => $this->contact_person_nama,
                    'no_telepon' => $this->contact_person_telepon ?: null,
                    'contact_person_spk_id' => $spk->id,
                ]);
            }

            // First rambu's survey photo doubles as the SPK's "cover" photo
            // elsewhere (see cover_photos on the list pages) — reused here so
            // the "SPK Baru Tersedia" Telegram notification arrives as an
            // actual photo instead of a bare text message.
            $fotoUtamaSpk = null;

            foreach ($this->rambuItems as $item) {
                $isPasangBaruItem = $item['jenis_pekerjaan'] === JenisPekerjaan::PasangBaru->value;

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
                    // Perbaikan untuk rambu yang secara fisik sudah ada tapi belum
                    // pernah dicatat di sistem. Beda dengan pasang baru: rambu ini
                    // sudah_terpasang sejak awal, dan dianggap rusak (makanya diperbaiki).
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

                $fotoSurvei = $this->resolveFotoSurvei($item);
                $fotoUtamaSpk ??= $fotoSurvei;

                RambuPasang::create([
                    'rambu_spk_id' => $spk->id,
                    'rambu_id' => $rambu->id,
                    'laporan_kondisi_id' => $item['laporan_kondisi_id'] ?: null,
                    'jenis_pekerjaan' => $item['jenis_pekerjaan'],
                    'jumlah' => $item['jumlah'],
                    'foto_survei' => $fotoSurvei,
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
                'spk_id' => $spk->id,
                'aksi' => 'spk_dibuat',
                'keterangan' => "SPK {$spk->nomor_surat} dibuat untuk wilayah {$spk->wilayah}.",
            ]);

            foreach (User::where('role', Role::User)->where('aktif', true)->get() as $petugas) {
                Notifikasi::create([
                    'user_id' => $petugas->id,
                    'judul' => 'SPK Baru Tersedia',
                    'pesan' => "SPK {$spk->nomor_surat} untuk wilayah {$spk->wilayah} sudah bisa dikerjakan.",
                    'url' => route('user.spk.show', $spk),
                    'foto' => $fotoUtamaSpk,
                    'dibaca' => false,
                ]);
            }

            PenyesuaianDeadlineSpk::terapkan($spk);

            return $spk;
        });

        Flux::toast(variant: 'success', text: "Surat {$spk->nomor_surat} berhasil dibuat.");

        $this->redirectRoute('admin.spk.index', navigate: true);
    }
}
