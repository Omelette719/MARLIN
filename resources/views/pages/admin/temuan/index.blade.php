    <div class="flex w-full flex-1 flex-col gap-6">
        <div>
            <flux:heading size="xl">Temuan Lapangan</flux:heading>
            <flux:subheading>Temuan kondisi rambu dari petugas yang belum ditindaklanjuti.</flux:subheading>
        </div>

        <flux:card class="flex-1">
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Rambu</flux:table.column>
                    <flux:table.column>Kondisi</flux:table.column>
                    <flux:table.column>Pelapor</flux:table.column>
                    <flux:table.column>Catatan</flux:table.column>
                    <flux:table.column>Tanggal</flux:table.column>
                    <flux:table.column align="end">Aksi</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse ($temuan as $item)
                        <flux:table.row>
                            <flux:table.cell variant="strong">{{ $item->rambu->wilayah }}, {{ $item->rambu->lokasi }}</flux:table.cell>
                            <flux:table.cell>
                                <flux:badge size="sm" :color="$item->kondisi_dilaporkan === \App\Enums\KondisiRambu::Rusak ? 'red' : 'green'">
                                    {{ $item->kondisi_dilaporkan->label() }}
                                </flux:badge>
                            </flux:table.cell>
                            <flux:table.cell>{{ $item->pelapor->name }}</flux:table.cell>
                            <flux:table.cell>{{ $item->catatan ?: 'Tidak ada catatan' }}</flux:table.cell>
                            <flux:table.cell>{{ $item->created_at->translatedFormat('d M Y') }}</flux:table.cell>
                            <flux:table.cell align="end">
                                <flux:button size="sm" variant="primary" :href="route('admin.spk.create', ['laporan_kondisi' => $item->id])" wire:navigate>
                                    Buat SPK
                                </flux:button>
                                <flux:button size="sm" variant="ghost" wire:click="tolak({{ $item->id }})" wire:confirm="Tandai temuan ini sebagai ditolak?">
                                    Tolak
                                </flux:button>
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="6" class="text-center text-zinc-500">
                                Tidak ada temuan yang belum ditindaklanjuti.
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </flux:card>
    </div>
