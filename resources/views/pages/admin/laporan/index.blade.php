    <div class="flex w-full flex-1 flex-col gap-6">
        <div class="flex items-end justify-between">
            <div>
                <flux:heading size="xl">Laporan Bulanan</flux:heading>
                <flux:subheading>Ringkasan riwayat pekerjaan untuk periode {{ $periodeLabel }}.</flux:subheading>
            </div>

            <div class="flex items-end gap-3">
                <flux:input type="month" wire:model.live="bulan" label="Periode" />
                <flux:button variant="primary" icon="arrow-down-tray" :href="route('admin.laporan.export', ['bulan' => $bulan])" target="_blank">
                    Unduh PDF
                </flux:button>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <flux:card class="flex items-center gap-4">
                <div class="flex size-12 shrink-0 items-center justify-center rounded-full bg-green-100 text-green-600">
                    <flux:icon icon="check-circle" class="size-6" />
                </div>
                <div class="min-w-0">
                    <flux:text class="text-sm text-zinc-500">Rambu Terpasang</flux:text>
                    <flux:heading size="xl">{{ $rambu['terpasang'] }}</flux:heading>
                </div>
            </flux:card>

            <flux:card class="flex items-center gap-4">
                <div class="flex size-12 shrink-0 items-center justify-center rounded-full bg-amber-100 text-amber-600">
                    <flux:icon icon="clock" class="size-6" />
                </div>
                <div class="min-w-0">
                    <flux:text class="text-sm text-zinc-500">Belum Terpasang</flux:text>
                    <flux:heading size="xl">{{ $rambu['belum_terpasang'] }}</flux:heading>
                </div>
            </flux:card>

            <flux:card class="flex items-center gap-4">
                <div class="flex size-12 shrink-0 items-center justify-center rounded-full bg-cyan-100 text-cyan-700">
                    <flux:icon icon="document-text" class="size-6" />
                </div>
                <div class="min-w-0">
                    <flux:text class="text-sm text-zinc-500">SPK Dibuat Bulan Ini</flux:text>
                    <flux:heading size="xl">{{ $spk['dibuat_bulan_ini'] }}</flux:heading>
                </div>
            </flux:card>

            <flux:card class="flex items-center gap-4">
                <div class="flex size-12 shrink-0 items-center justify-center rounded-full bg-red-100 text-red-600">
                    <flux:icon icon="exclamation-triangle" class="size-6" />
                </div>
                <div class="min-w-0">
                    <flux:text class="text-sm text-zinc-500">Kendala Bulan Ini</flux:text>
                    <flux:heading size="xl">{{ $kendalaBulanIni }}</flux:heading>
                </div>
            </flux:card>
        </div>

        <flux:card class="flex flex-col gap-3">
            <flux:heading size="lg">Kondisi Aset Rambu (saat ini)</flux:heading>
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-4 text-sm">
                <div><flux:text class="text-zinc-500">Total Rambu</flux:text><flux:heading size="sm">{{ $rambu['total'] }}</flux:heading></div>
                <div><flux:text class="text-zinc-500">Kondisi Baik</flux:text><flux:heading size="sm">{{ $rambu['kondisi_baik'] }}</flux:heading></div>
                <div><flux:text class="text-zinc-500">Kondisi Rusak</flux:text><flux:heading size="sm">{{ $rambu['kondisi_rusak'] }}</flux:heading></div>
                <div><flux:text class="text-zinc-500">Temuan Kondisi Bulan Ini</flux:text><flux:heading size="sm">{{ $temuanBulanIni }}</flux:heading></div>
            </div>
        </flux:card>

        <flux:card class="flex flex-col gap-3">
            <flux:heading size="lg">SPK Selesai Bulan Ini ({{ $spkSelesaiBulanIni->count() }})</flux:heading>

            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Nomor Surat</flux:table.column>
                    <flux:table.column>Wilayah</flux:table.column>
                    <flux:table.column>Jenis</flux:table.column>
                    <flux:table.column align="center">Jumlah Rambu</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse ($spkSelesaiBulanIni as $item)
                        <flux:table.row>
                            <flux:table.cell variant="strong">{{ $item->nomor_surat }}</flux:table.cell>
                            <flux:table.cell>{{ $item->wilayah }}</flux:table.cell>
                            <flux:table.cell>{{ $item->jenis_spk->label() }}</flux:table.cell>
                            <flux:table.cell align="center">{{ $item->rambu_pasang_count }}</flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="4" class="text-center text-zinc-500">Tidak ada SPK yang selesai pada periode ini.</flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </flux:card>

        <flux:card class="flex flex-col gap-3">
            <flux:heading size="lg">SPK Belum Selesai ({{ $spkAktif->count() }})</flux:heading>

            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Nomor Surat</flux:table.column>
                    <flux:table.column>Wilayah</flux:table.column>
                    <flux:table.column>Deadline</flux:table.column>
                    <flux:table.column align="center">Progres</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse ($spkAktif as $item)
                        <flux:table.row>
                            <flux:table.cell variant="strong">{{ $item->nomor_surat }}</flux:table.cell>
                            <flux:table.cell>{{ $item->wilayah }}</flux:table.cell>
                            <flux:table.cell>{{ $item->deadline->translatedFormat('d M Y') }}</flux:table.cell>
                            <flux:table.cell align="center">{{ $item->selesai_count }}/{{ $item->rambu_pasang_count }}</flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="4" class="text-center text-zinc-500">Tidak ada SPK aktif saat ini.</flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </flux:card>
    </div>
