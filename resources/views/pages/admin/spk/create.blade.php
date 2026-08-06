    @php
        use Illuminate\Support\Facades\Storage;
    @endphp

    <div class="flex w-full flex-1 flex-col gap-6">
        <div>
            <flux:heading size="xl">Buat Surat</flux:heading>
            <flux:subheading>Buat Surat Perintah Kerja (SPK) untuk pemasangan baru atau perbaikan rambu.</flux:subheading>
        </div>

        <form wire:submit="save" class="flex flex-col gap-6">
            <flux:card class="flex flex-col gap-4">
                <flux:heading size="lg">Detail Surat</flux:heading>

                <div>
                    <flux:radio.group wire:model.live="jenis_spk" label="Jenis Surat" variant="segmented">
                        <flux:radio value="{{ \App\Enums\JenisPekerjaan::PasangBaru->value }}" label="Pemasangan Baru" />
                        <flux:radio value="{{ \App\Enums\JenisPekerjaan::Perbaikan->value }}" label="Perbaikan" />
                    </flux:radio.group>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <flux:input wire:model="jalan" label="Jalan" placeholder="Mis. Gatot X" required />
                    <flux:input wire:model="rt" label="RT" placeholder="Mis. 27" required />
                    <flux:input wire:model="kelurahan" label="Kelurahan" placeholder="Mis. Pengambangan" required />
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <flux:input wire:model="perihal" label="Perihal Permohonan" placeholder="Mis. pemasangan cermin tikungan" description="Opsional. Kalau kosong, akan dibuat otomatis dari jenis pekerjaan &amp; jenis rambu." />
                    <flux:input wire:model="deadline" type="date" label="Deadline" required />
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <flux:select wire:model="asal_permintaan" label="Asal Permintaan" placeholder="Pilih asal permintaan" required>
                        @foreach ($asalPermintaanOptions as $opt)
                            <flux:select.option value="{{ $opt->value }}">{{ $opt->label() }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:input wire:model="keterangan_asal" label="Keterangan Asal" placeholder="Opsional, mis. nama pelapor/instansi" />
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <flux:input wire:model="tanggal_survei" type="date" label="Tanggal Survei" description="Opsional. Kalau diisi, akan muncul di surat pengantar sebagai '(DISURVEI TGL ...)'." />
                    <flux:input wire:model="petugas_survei" label="Petugas Survei" placeholder="Nama-nama petugas, pisahkan dengan koma" description="Wajib diisi jika tanggal survei diisi. Tidak muncul di surat pengantar." />
                </div>

                <flux:checkbox wire:model="prioritas" label="Tandai sebagai prioritas" description="Jika dicentang, urgensi otomatis menjadi Tinggi terlepas dari deadline." />

                <flux:input wire:model="file_referensi" type="file" accept="image/*,application/pdf" label="File Referensi" description="Opsional, scan/foto surat permohonan asli dari RT/warga/pemerintah (gambar atau PDF)." />

                <flux:textarea wire:model="catatan_pekerja_tambahan" label="Catatan" placeholder="Catatan tambahan untuk pekerja (opsional)" rows="3" />
            </flux:card>

            <flux:card class="flex flex-col gap-4">
                <div>
                    <flux:heading size="lg">Contact Person</flux:heading>
                    <flux:subheading>Opsional. Kontak warga/perwakilan setempat yang bisa dihubungi petugas di lapangan — bukan identitas resmi RT (nama RT ditulis tangan langsung di surat saat petugas datang meminta izin).</flux:subheading>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <flux:input wire:model="rt_nama" label="Nama Contact Person" placeholder="Mis. Abdul (warga setempat)" />
                    <flux:input wire:model="rt_telepon" label="No. Telepon Contact Person" placeholder="Opsional" />
                </div>
            </flux:card>

            <flux:card class="flex flex-col gap-4">
                <flux:heading size="lg">Daftar Rambu</flux:heading>

                {{--
                    deep="false": Flux's <flux:error> falls back to a
                    wildcard "rambuItems.*" lookup by default when there's no
                    exact "rambuItems" error, which meant this array-level
                    slot (meant only for "Tambahkan minimal satu rambu.")
                    also duplicated whatever per-item field error already
                    renders inline inside that item's own card below.
                --}}
                <flux:error name="rambuItems" :deep="false" />

                @foreach ($rambuItems as $index => $item)
                    <flux:card wire:key="rambu-{{ $index }}" class="flex flex-col gap-4 bg-zinc-50">
                        <div class="flex items-center justify-between">
                            <flux:heading size="sm">Rambu #{{ $index + 1 }}</flux:heading>
                            @if (count($rambuItems) > 1)
                                <flux:button type="button" size="sm" variant="danger" icon="trash" wire:click="removeRambuItem({{ $index }})" />
                            @endif
                        </div>

                        <x-photo-upload
                            model="rambuItems.{{ $index }}.foto_survei"
                            label="Foto Tempat"
                            :file="$item['foto_survei']"
                            :existing-url="$item['foto_survei_existing'] ? Storage::url($item['foto_survei_existing']) : null"
                            :description="$item['foto_survei_existing'] ? 'Foto dari laporan temuan ini akan dipakai kecuali diganti dengan upload baru.' : null"
                            class="max-w-sm"
                        />

                        @if ($jenis_spk === \App\Enums\JenisPekerjaan::Perbaikan->value)
                            <flux:checkbox wire:model="rambuItems.{{ $index }}.rambu_terdaftar" label="Rambu sudah terdaftar di sistem" description="Matikan jika rambu ini sudah ada secara fisik tapi belum pernah dicatat di sistem." />
                        @endif

                        @if ($jenis_spk === \App\Enums\JenisPekerjaan::PasangBaru->value || ! $item['rambu_terdaftar'])
                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <x-searchable-select
                                    wire-model="rambuItems.{{ $index }}.jenis_rambu_id"
                                    :options="$jenisRambuSelectOptions"
                                    label="Jenis Rambu"
                                    placeholder="Cari jenis rambu"
                                />
                            </div>
                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <flux:input wire:model="rambuItems.{{ $index }}.lokasi" label="Lokasi" placeholder="Mis. perempatan 1, samping masjid" />
                                <flux:input wire:model.live.debounce.500ms="rambuItems.{{ $index }}.koordinat" label="Koordinat" placeholder="-3.3194,114.5908" description="Format: lintang,bujur" />
                            </div>

                            @if (! empty($koordinatWarnings[$index] ?? null))
                                <flux:callout variant="warning" icon="exclamation-triangle" heading="Ada rambu lain di lokasi yang sama/berdekatan">
                                    <ul class="list-disc pl-4">
                                        @foreach ($koordinatWarnings[$index] as $peringatan)
                                            <li>{{ $peringatan['label'] }} ({{ $peringatan['jarak'] }} m)</li>
                                        @endforeach
                                    </ul>
                                    Periksa dulu supaya rambu ini tidak terdaftar dua kali.
                                </flux:callout>
                            @endif
                        @else
                            <x-searchable-select
                                wire-model="rambuItems.{{ $index }}.rambu_id"
                                :options="$rambuSelectOptions"
                                label="Pilih Rambu Existing"
                                placeholder="Cari rambu berdasarkan wilayah/lokasi"
                            />
                        @endif

                        <flux:input wire:model="rambuItems.{{ $index }}.jumlah" type="number" min="1" label="Jumlah" class="sm:max-w-40" />

                        <flux:textarea wire:model="rambuItems.{{ $index }}.catatan_instruksi" label="Info / Catatan Instruksi" placeholder="Mis. apa yang perlu dibawa petugas" rows="2" />
                    </flux:card>
                @endforeach

                <flux:button type="button" variant="primary" icon="plus" wire:click="addRambuItem" class="self-start">
                    Tambah Rambu
                </flux:button>
            </flux:card>

            <div class="flex justify-end gap-3">
                <flux:button variant="ghost" :href="route('admin.spk.index')" wire:navigate>Batal</flux:button>
                <flux:button type="submit" variant="primary">Simpan Surat</flux:button>
            </div>
        </form>
    </div>
