    @php
        use App\Enums\StatusRambuPasang;
    @endphp

    <div class="flex w-full flex-1 flex-col gap-6">
        <div class="flex items-end justify-between">
            <div>
                <flux:heading size="xl">Laporan Rambu</flux:heading>
                <flux:subheading>Riwayat pekerjaan rambu, bisa difilter bebas per jenis rambu, status, dan rentang tanggal.</flux:subheading>
            </div>

            <flux:button variant="primary" icon="arrow-down-tray" :href="route('admin.laporan.rambu-export', [
                'tanggal_dari' => $tanggal_dari,
                'tanggal_sampai' => $tanggal_sampai,
                'jenis_rambu_id' => $jenis_rambu_id,
                'status' => $status,
            ])" target="_blank">
                Unduh PDF
            </flux:button>
        </div>

        <flux:card class="flex flex-col gap-4">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-4">
                <flux:input type="date" wire:model.live="tanggal_dari" label="Dari Tanggal" />
                <flux:input type="date" wire:model.live="tanggal_sampai" label="Sampai Tanggal" />

                <flux:select wire:model.live="jenis_rambu_id" label="Jenis Rambu" placeholder="Semua Jenis">
                    <flux:select.option value="">Semua Jenis</flux:select.option>
                    @foreach ($jenisRambuOptions as $j)
                        <flux:select.option value="{{ $j->id }}">{{ $j->nama_jenis }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select wire:model.live="status" label="Status" placeholder="Semua Status">
                    <flux:select.option value="">Semua Status</flux:select.option>
                    @foreach ($statusOptions as $s)
                        <flux:select.option value="{{ $s->value }}">{{ $s->label() }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>
        </flux:card>

        <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
            <flux:card class="flex flex-col gap-1">
                <flux:text class="text-sm text-zinc-500">Total Hasil</flux:text>
                <flux:heading size="xl">{{ $total }}</flux:heading>
            </flux:card>
            @foreach ($statusOptions as $s)
                <flux:card class="flex flex-col gap-1">
                    <flux:text class="text-sm text-zinc-500">{{ $s->label() }}</flux:text>
                    <flux:heading size="xl">{{ $perStatus[$s->value] ?? 0 }}</flux:heading>
                </flux:card>
            @endforeach
        </div>

        <flux:card class="flex flex-col gap-3">
            <flux:heading size="lg">Daftar Rambu ({{ $total }})</flux:heading>

            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Nomor Surat</flux:table.column>
                    <flux:table.column>Jenis Rambu</flux:table.column>
                    <flux:table.column>Lokasi</flux:table.column>
                    <flux:table.column>Tanggal</flux:table.column>
                    <flux:table.column align="end">Status</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse ($items as $item)
                        <flux:table.row>
                            <flux:table.cell variant="strong">{{ $item->spk->nomor_surat }}</flux:table.cell>
                            <flux:table.cell>{{ $item->rambu->jenisRambu?->nama_jenis }}</flux:table.cell>
                            <flux:table.cell>{{ $item->rambu->wilayah }}, {{ $item->rambu->lokasi }}</flux:table.cell>
                            <flux:table.cell>{{ $item->created_at->translatedFormat('d M Y') }}</flux:table.cell>
                            <flux:table.cell align="end">
                                <flux:badge size="sm" :color="match ($item->status) {
                                    StatusRambuPasang::Selesai => 'green',
                                    StatusRambuPasang::MenungguValidasi => 'blue',
                                    StatusRambuPasang::Urgent, StatusRambuPasang::Revisi => 'red',
                                    StatusRambuPasang::Tertunda => 'amber',
                                    StatusRambuPasang::Batal => 'zinc',
                                    default => 'zinc',
                                }">{{ $item->status->label() }}</flux:badge>

                                @if ($item->status === StatusRambuPasang::Batal && $item->catatan_pembatalan)
                                    <flux:text class="mt-1 text-xs text-zinc-500">{{ $item->catatan_pembatalan }}</flux:text>
                                @elseif ($item->status === StatusRambuPasang::Tertunda && $item->kendala->first()?->alasan)
                                    <flux:text class="mt-1 text-xs text-zinc-500">{{ $item->kendala->first()->alasan }}</flux:text>
                                @endif
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="5" class="text-center text-zinc-500">Tidak ada data untuk filter ini.</flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </flux:card>
    </div>
