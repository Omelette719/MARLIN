    <div class="flex w-full flex-1 flex-col gap-6">
        <div>
            <flux:heading size="xl">Laporan Kendala Lapangan</flux:heading>
            <flux:subheading>Ajukan kendala jika ada rambu yang tidak bisa dikerjakan sesuai rencana. Hanya perwakilan tim yang bisa mengajukan.</flux:subheading>
        </div>

        @if ($item)
            <flux:card class="flex flex-col gap-4">
                <div>
                    <flux:heading size="lg">{{ $item->spk->nomor_surat }}</flux:heading>
                    <flux:subheading>{{ $item->rambu->wilayah }}, {{ $item->rambu->lokasi }} ({{ $item->jenis_pekerjaan->label() }})</flux:subheading>
                </div>

                <form wire:submit="submit" class="flex flex-col gap-4">
                    <flux:textarea wire:model="alasan" label="Catatan Kendala" placeholder="Jelaskan kendala yang dihadapi di lapangan" rows="4" required />

                    <div>
                        <flux:input type="file" wire:model="foto" label="Foto Bukti Kendala" required />

                        @if ($foto)
                            <div class="mt-2 flex h-32 w-48 items-center justify-center overflow-hidden rounded-lg border border-zinc-200 bg-zinc-50">
                                <img src="{{ $foto->temporaryUrl() }}" class="size-full object-cover" />
                            </div>
                        @endif
                    </div>

                    <div class="flex justify-end gap-3">
                        <flux:button type="button" variant="ghost" wire:click="back">Batal</flux:button>
                        <flux:button type="submit" variant="primary">Ajukan Kendala</flux:button>
                    </div>
                </form>
            </flux:card>
        @else
            <flux:card class="flex-1">
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>Nomor Surat</flux:table.column>
                        <flux:table.column>Lokasi</flux:table.column>
                        <flux:table.column>Jenis Pekerjaan</flux:table.column>
                        <flux:table.column align="end">Aksi</flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @forelse ($items as $rp)
                            <flux:table.row>
                                <flux:table.cell variant="strong">{{ $rp->spk->nomor_surat }}</flux:table.cell>
                                <flux:table.cell>{{ $rp->rambu->wilayah }}, {{ $rp->rambu->lokasi }}</flux:table.cell>
                                <flux:table.cell>{{ $rp->jenis_pekerjaan->label() }}</flux:table.cell>
                                <flux:table.cell align="end">
                                    <flux:button size="sm" variant="primary" wire:click="selectItem({{ $rp->id }})">Ajukan Kendala</flux:button>
                                </flux:table.cell>
                            </flux:table.row>
                        @empty
                            <flux:table.row>
                                <flux:table.cell colspan="4" class="text-center text-zinc-500">
                                    Tidak ada tugas yang bisa diajukan kendala saat ini.
                                </flux:table.cell>
                            </flux:table.row>
                        @endforelse
                    </flux:table.rows>
                </flux:table>
            </flux:card>
        @endif
    </div>
