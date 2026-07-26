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

                <flux:input wire:model="tanggal_survei" type="date" label="Tanggal Survei" description="Opsional. Kalau diisi, akan muncul di surat pengantar sebagai '(DISURVEI TGL ...)'." />

                <flux:checkbox wire:model="prioritas" label="Tandai sebagai prioritas" description="Jika dicentang, urgensi otomatis menjadi Tinggi terlepas dari deadline." />

                <flux:input wire:model="file_referensi" type="file" label="File Referensi" description="Opsional, scan surat permohonan asli dari RT/warga/pemerintah." />

                <flux:textarea wire:model="catatan_pekerja_tambahan" label="Catatan" placeholder="Catatan tambahan untuk pekerja (opsional)" rows="3" />
            </flux:card>

            <flux:card class="flex flex-col gap-4">
                <div>
                    <flux:heading size="lg">RT / Perwakilan Warga</flux:heading>
                    <flux:subheading>Opsional. Kontak RT/perwakilan yang akan mengetahui &amp; menandatangani surat pengantar secara manual.</flux:subheading>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <flux:input wire:model="rt_nama" label="Nama RT / Perwakilan" placeholder="Mis. RT. 27 Matoha" />
                    <flux:input wire:model="rt_telepon" label="No. Telepon" placeholder="Opsional" />
                </div>
            </flux:card>

            <flux:card class="flex flex-col gap-4">
                <flux:heading size="lg">Daftar Rambu</flux:heading>

                @error('rambuItems') <flux:error>{{ $message }}</flux:error> @enderror

                @foreach ($rambuItems as $index => $item)
                    <flux:card wire:key="rambu-{{ $index }}" class="flex flex-col gap-4 bg-zinc-50">
                        <div class="flex items-center justify-between">
                            <flux:heading size="sm">Rambu #{{ $index + 1 }}</flux:heading>
                            @if (count($rambuItems) > 1)
                                <flux:button type="button" size="sm" variant="danger" icon="trash" wire:click="removeRambuItem({{ $index }})" />
                            @endif
                        </div>

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
                                <flux:input wire:model="rambuItems.{{ $index }}.koordinat" label="Koordinat" placeholder="-3.3194,114.5908" />
                            </div>
                        @else
                            <x-searchable-select
                                wire-model="rambuItems.{{ $index }}.rambu_id"
                                :options="$rambuSelectOptions"
                                label="Pilih Rambu Existing"
                                placeholder="Cari rambu berdasarkan wilayah/lokasi"
                            />
                        @endif

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <flux:input wire:model="rambuItems.{{ $index }}.jumlah" type="number" min="1" label="Jumlah" />
                            <div>
                                <flux:input wire:model="rambuItems.{{ $index }}.foto_survei" type="file" label="Foto Tempat" />

                                @if ($item['foto_survei'])
                                    <div class="mt-2 flex h-24 w-32 items-center justify-center overflow-hidden rounded-lg border border-zinc-200 bg-zinc-50">
                                        <img src="{{ $item['foto_survei']->temporaryUrl() }}" class="size-full object-cover" />
                                    </div>
                                @endif
                            </div>
                        </div>

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
