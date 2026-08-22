    <div class="flex w-full flex-1 flex-col gap-6">
        <div>
            <flux:heading size="xl">Validasi Laporan</flux:heading>
            <flux:subheading>Surat yang laporan akhirnya sudah diajukan perwakilan tim dan menunggu validasi.</flux:subheading>
        </div>

        <flux:card class="flex-1">
            <x-table-scroll-hint />
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Nomor Surat</flux:table.column>
                    <flux:table.column>Wilayah</flux:table.column>
                    <flux:table.column>Deadline</flux:table.column>
                    <flux:table.column align="center">Selesai</flux:table.column>
                    <flux:table.column align="center">Terkendala</flux:table.column>
                    <flux:table.column align="end">Aksi</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse ($spk as $item)
                        <flux:table.row>
                            <flux:table.cell variant="strong">{{ $item->nomor_surat }}</flux:table.cell>
                            <flux:table.cell>{{ $item->wilayah }}</flux:table.cell>
                            <flux:table.cell>{{ $item->deadline->translatedFormat('d M Y') }}</flux:table.cell>
                            <flux:table.cell align="center">
                                <flux:badge color="green" size="sm">{{ $item->selesai_count }}</flux:badge>
                            </flux:table.cell>
                            <flux:table.cell align="center">
                                <flux:badge color="amber" size="sm">{{ $item->terkendala_count }}</flux:badge>
                            </flux:table.cell>
                            <flux:table.cell align="end">
                                <flux:button size="sm" variant="primary" :href="route('admin.validasi.show', $item)" wire:navigate>
                                    Validasi Sekarang
                                </flux:button>
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="6" class="text-center text-zinc-500">
                                Belum ada laporan akhir yang diajukan saat ini.
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </flux:card>
    </div>
