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

                <div>
                    <flux:input wire:model="file_referensi" type="file" accept="image/*" label="Ganti File Referensi" description="Opsional, scan/foto surat permohonan asli dari RT/warga/pemerintah (gambar saja)." />
                    @if ($spk->file_referensi && ! $file_referensi)
                        <flux:text class="mt-1 text-sm text-zinc-500">
                            File saat ini: <flux:link :href="\Illuminate\Support\Facades\Storage::url($spk->file_referensi)" target="_blank">lihat file</flux:link>
                        </flux:text>
                    @endif
                </div>

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

            <div class="flex justify-end gap-3">
                <flux:button variant="ghost" :href="route('admin.spk.show', $spk)" wire:navigate>Batal</flux:button>
                <flux:button type="submit" variant="primary">Simpan Perubahan</flux:button>
            </div>
        </form>
    </div>
