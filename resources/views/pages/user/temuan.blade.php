    <div class="flex w-full flex-1 flex-col gap-6">
        <div>
            <flux:heading size="xl">Laporan Temuan Kondisi</flux:heading>
            <flux:subheading>Laporkan rambu yang ditemukan rusak/tidak layak saat patroli atau di sela tugas lain.</flux:subheading>
        </div>

        <flux:card class="max-w-2xl">
            <form wire:submit="submit" class="flex flex-col gap-4">
                <x-searchable-select
                    wire-model="rambu_id"
                    :options="$rambuSelectOptions"
                    label="Pilih Rambu"
                    placeholder="Cari rambu berdasarkan wilayah/lokasi"
                    required
                />

                <flux:select wire:model="kondisi_dilaporkan" label="Kondisi yang Ditemukan">
                    @foreach ($kondisiOptions as $k)
                        <flux:select.option value="{{ $k->value }}">{{ $k->label() }}</flux:select.option>
                    @endforeach
                </flux:select>

                <x-photo-upload model="foto" label="Foto Bukti" :file="$foto" required />

                <flux:textarea wire:model="catatan" label="Catatan" placeholder="Jelaskan kondisi yang ditemukan (opsional)" rows="3" />

                <div class="flex justify-end">
                    <flux:button type="submit" variant="primary">Kirim Laporan</flux:button>
                </div>
            </form>
        </flux:card>
    </div>
