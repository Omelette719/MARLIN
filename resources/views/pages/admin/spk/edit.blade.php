    @php
        use App\Enums\StatusRambuPasang;
        use Illuminate\Support\Facades\Storage;
    @endphp

    <div>
    <div class="flex w-full flex-1 flex-col gap-6">
        <div class="flex items-end justify-between">
            <div>
                <flux:heading size="xl">Edit Surat</flux:heading>
                <flux:subheading>{{ $spk->nomor_surat }}</flux:subheading>
            </div>

            <flux:button variant="ghost" :href="route('admin.spk.show', $spk)" wire:navigate>Kembali</flux:button>
        </div>

        <form wire:submit="save" class="flex flex-col gap-6">
            <flux:card class="flex flex-col gap-4">
                <flux:heading size="lg">Detail Surat</flux:heading>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <flux:input wire:model="jalan" label="Jalan" placeholder="Mis. Gatot X" required />
                    <flux:input wire:model.live.debounce.500ms="rt" label="RT" placeholder="Mis. 27" required />
                    <flux:input wire:model="kelurahan" label="Kelurahan" placeholder="Mis. Pengambangan" required />
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <flux:input wire:model="perihal" label="Perihal Permohonan" placeholder="Mis. pemasangan cermin tikungan" description="Opsional. Kalau kosong, akan dibuat otomatis dari jenis pekerjaan &amp; jenis rambu." />
                    <flux:input wire:model.live.debounce.500ms="deadline" type="date" label="Deadline" required />
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
                    <flux:input wire:model.live.debounce.500ms="tanggal_survei" type="date" label="Tanggal Survei" description="Opsional. Kalau diisi, akan muncul di surat pengantar sebagai '(DISURVEI TGL ...)'." />
                    <flux:input wire:model.live.debounce.500ms="petugas_survei" label="Petugas Survei" placeholder="Nama-nama petugas, pisahkan dengan koma" description="Wajib diisi jika tanggal survei diisi. Tidak muncul di surat pengantar." />
                </div>

                <flux:checkbox wire:model="prioritas" label="Tandai sebagai prioritas" description="Jika dicentang, urgensi otomatis menjadi Tinggi terlepas dari deadline." />

                <div>
                    <flux:input wire:model="file_referensi" type="file" accept="image/*,application/pdf" label="Ganti File Referensi" description="Opsional, scan/foto surat permohonan asli dari RT/warga/pemerintah (gambar atau PDF)." />
                    @if ($spk->file_referensi && ! $file_referensi)
                        <flux:text class="mt-1 text-sm text-zinc-500">
                            File saat ini: <flux:link :href="Storage::url($spk->file_referensi)" target="_blank">lihat file</flux:link>
                        </flux:text>
                    @endif
                </div>

                <flux:textarea wire:model="catatan_pekerja_tambahan" label="Catatan" placeholder="Catatan tambahan untuk pekerja (opsional)" rows="3" />
            </flux:card>

            <flux:card class="flex flex-col gap-4">
                <div>
                    <flux:heading size="lg">Contact Person</flux:heading>
                    <flux:subheading>Opsional. Kontak warga/perwakilan setempat yang bisa dihubungi petugas di lapangan — bukan identitas resmi RT (nama RT ditulis tangan langsung di surat saat petugas datang meminta izin).</flux:subheading>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <flux:input wire:model.live.debounce.500ms="rt_nama" label="Nama Contact Person" placeholder="Mis. Abdul (warga setempat)" />
                    <flux:input wire:model.live.debounce.500ms="rt_telepon" label="No. Telepon Contact Person" placeholder="Opsional" />
                </div>
            </flux:card>

            <flux:card class="flex flex-col gap-4">
                <flux:heading size="lg">Daftar Rambu</flux:heading>

                {{--
                    deep="false": see create.blade.php — without it this
                    array-level slot (meant only for "Minimal harus ada satu
                    rambu.") duplicates whatever per-item field error already
                    renders inline inside that item's own card below.
                --}}
                <flux:error name="rambuItems" :deep="false" />

                @foreach ($rambuItems as $index => $item)
                    <flux:card wire:key="rambu-{{ $item['id'] ?? 'new-'.$index }}" class="flex flex-col gap-4 bg-zinc-50">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <flux:heading size="sm">Rambu #{{ $index + 1 }}</flux:heading>
                                @if ($item['status'])
                                    <flux:badge size="sm" :color="match (StatusRambuPasang::from($item['status'])) {
                                        StatusRambuPasang::Selesai => 'green',
                                        StatusRambuPasang::MenungguValidasi => 'blue',
                                        StatusRambuPasang::Urgent, StatusRambuPasang::Revisi => 'red',
                                        StatusRambuPasang::Tertunda => 'amber',
                                        StatusRambuPasang::Batal => 'zinc',
                                        default => 'zinc',
                                    }">{{ StatusRambuPasang::from($item['status'])->label() }}</flux:badge>
                                @endif
                            </div>

                            @if (! $item['id'])
                                <flux:button type="button" size="sm" variant="danger" icon="trash" wire:click="removeRambuItem({{ $index }})" />
                            @endif
                        </div>

                        @if ($item['status'] === 'batal' && $item['catatan_pembatalan'])
                            <flux:text class="text-sm text-red-600">Dibatalkan: {{ $item['catatan_pembatalan'] }}</flux:text>
                        @endif

                        <x-photo-upload
                            model="rambuItems.{{ $index }}.foto_survei"
                            label="Foto Tempat"
                            :file="$item['foto_survei']"
                            :existing-url="$item['existing_foto_survei'] ? Storage::url($item['existing_foto_survei']) : null"
                            class="max-w-sm"
                        />

                        <flux:radio.group wire:model.live="rambuItems.{{ $index }}.jenis_pekerjaan" label="Jenis Pekerjaan" variant="segmented">
                            <flux:radio value="{{ \App\Enums\JenisPekerjaan::PasangBaru->value }}" label="Pemasangan Baru" />
                            <flux:radio value="{{ \App\Enums\JenisPekerjaan::Perbaikan->value }}" label="Perbaikan" />
                        </flux:radio.group>

                        @if ($item['jenis_pekerjaan'] === \App\Enums\JenisPekerjaan::Perbaikan->value)
                            <flux:checkbox wire:model="rambuItems.{{ $index }}.rambu_terdaftar" label="Rambu sudah terdaftar di sistem" description="Matikan untuk mengubah data rambu ini (jenis/lokasi/koordinat) langsung." />
                        @endif

                        @if ($item['jenis_pekerjaan'] === \App\Enums\JenisPekerjaan::PasangBaru->value || ! $item['rambu_terdaftar'])
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

                        @if ($item['id'])
                            <div class="flex justify-end gap-2 border-t border-zinc-200 pt-3">
                                @if ($item['status'] !== 'batal')
                                    <flux:button type="button" size="sm" variant="danger" wire:click="bukaBatalkanRambu({{ $index }})">
                                        Batalkan
                                    </flux:button>
                                @endif
                                @if ($item['can_hapus'])
                                    <flux:modal.trigger name="hapus-rambu-{{ $index }}">
                                        <flux:button type="button" size="sm" variant="danger">
                                            Hapus
                                        </flux:button>
                                    </flux:modal.trigger>

                                    <x-confirm-modal
                                        wire:key="hapus-rambu-modal-{{ $item['id'] ?? 'new-'.$index }}"
                                        name="hapus-rambu-{{ $index }}"
                                        heading="Hapus rambu ini dari surat?"
                                        text="Rambu akan dihapus permanen dari daftar surat ini. Tindakan ini tidak bisa dibatalkan."
                                        action="hapusRambu({{ $index }})"
                                        confirm-label="Ya, Hapus"
                                        tone="danger"
                                    />
                                @endif
                            </div>
                        @endif
                    </flux:card>
                @endforeach

                <flux:button type="button" variant="primary" icon="plus" wire:click="addRambuItem" class="self-start">
                    Tambah Rambu
                </flux:button>
            </flux:card>

            <div class="flex justify-end gap-3">
                <flux:button variant="ghost" :href="route('admin.spk.show', $spk)" wire:navigate>Batal</flux:button>
                <flux:button type="submit" variant="primary">Simpan Perubahan</flux:button>
            </div>
        </form>
    </div>

    <flux:modal name="batalkan-rambu" class="max-w-md">
        <form wire:submit="konfirmasiBatalkanRambu" class="flex flex-col gap-4">
            <flux:heading size="lg">Batalkan Rambu</flux:heading>
            <flux:textarea wire:model="catatan_pembatalan" label="Alasan Pembatalan" rows="3" required />
            <div class="flex justify-end gap-3">
                <flux:modal.close>
                    <flux:button type="button" variant="ghost">Batal</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="danger">Konfirmasi Batalkan</flux:button>
            </div>
        </form>
    </flux:modal>
    </div>
