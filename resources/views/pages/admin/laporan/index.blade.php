    @php
        use App\Enums\StatusRambuPasang;
    @endphp

    <div class="flex w-full flex-1 flex-col gap-6">
        <div class="flex items-end justify-between">
            <div>
                <flux:heading size="xl">Laporan Bulanan</flux:heading>
                <flux:subheading>Ringkasan riwayat pekerjaan untuk periode {{ $periodeLabel }}.</flux:subheading>
            </div>

            <flux:button variant="primary" icon="arrow-down-tray" :href="route('admin.laporan.export', [
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

                <flux:select wire:model.live="status" label="Status Rambu" placeholder="Semua Status" description="Menyaring tabel Detail Rambu di bawah.">
                    <flux:select.option value="">Semua Status</flux:select.option>
                    @foreach ($statusOptions as $s)
                        <flux:select.option value="{{ $s->value }}">{{ $s->label() }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>
        </flux:card>

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
                    <flux:text class="text-sm text-zinc-500">SPK Dibuat Periode Ini</flux:text>
                    <flux:heading size="xl">{{ $spk['dibuat_periode'] }}</flux:heading>
                </div>
            </flux:card>

            <flux:card class="flex items-center gap-4">
                <div class="flex size-12 shrink-0 items-center justify-center rounded-full bg-red-100 text-red-600">
                    <flux:icon icon="exclamation-triangle" class="size-6" />
                </div>
                <div class="min-w-0">
                    <flux:text class="text-sm text-zinc-500">Kendala Periode Ini</flux:text>
                    <flux:heading size="xl">{{ $kendalaPeriode }}</flux:heading>
                </div>
            </flux:card>
        </div>

        <flux:card class="flex flex-col gap-3">
            <flux:heading size="lg">Kondisi Aset Rambu (saat ini)</flux:heading>
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-4 text-sm">
                <div><flux:text class="text-zinc-500">Total Rambu</flux:text><flux:heading size="sm">{{ $rambu['total'] }}</flux:heading></div>
                <div><flux:text class="text-zinc-500">Kondisi Baik</flux:text><flux:heading size="sm">{{ $rambu['kondisi_baik'] }}</flux:heading></div>
                <div><flux:text class="text-zinc-500">Kondisi Rusak</flux:text><flux:heading size="sm">{{ $rambu['kondisi_rusak'] }}</flux:heading></div>
                <div><flux:text class="text-zinc-500">Temuan Kondisi Periode Ini</flux:text><flux:heading size="sm">{{ $temuanPeriode }}</flux:heading></div>
            </div>
        </flux:card>

        <flux:card class="flex flex-col gap-3">
            <div>
                <flux:heading size="lg">SPK Selesai Periode Ini ({{ $spkSelesaiPeriode->count() }})</flux:heading>
                <flux:subheading>
                    Rata-rata durasi pengerjaan:
                    {{ $analitikSelesai['rata_rata_durasi_hari'] !== null ? $analitikSelesai['rata_rata_durasi_hari'].' hari' : '-' }}
                    &middot; Rata-rata selisih deadline:
                    {{ $analitikSelesai['rata_rata_selisih_deadline_hari'] !== null ? $analitikSelesai['rata_rata_selisih_deadline_hari'].' hari' : '-' }}
                    &middot; Tepat waktu/lebih cepat: {{ $analitikSelesai['tepat_waktu_count'] }}
                    &middot; Terlambat: {{ $analitikSelesai['terlambat_count'] }}
                </flux:subheading>
            </div>

            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Nomor Surat</flux:table.column>
                    <flux:table.column>Wilayah</flux:table.column>
                    <flux:table.column>Jenis</flux:table.column>
                    <flux:table.column align="center">Jumlah Rambu</flux:table.column>
                    <flux:table.column align="center">Durasi</flux:table.column>
                    <flux:table.column align="center">Selisih Deadline</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse ($spkSelesaiPeriode as $item)
                        <flux:table.row>
                            <flux:table.cell variant="strong">{{ $item->nomor_surat }}</flux:table.cell>
                            <flux:table.cell>{{ $item->wilayah }}</flux:table.cell>
                            <flux:table.cell>{{ $item->jenis_spk->label() }}</flux:table.cell>
                            <flux:table.cell align="center">{{ $item->rambu_pasang_count }}</flux:table.cell>
                            <flux:table.cell align="center">{{ $item->durasiPengerjaanHari() !== null ? $item->durasiPengerjaanHari().' hari' : '-' }}</flux:table.cell>
                            <flux:table.cell align="center">{{ $item->selisihDeadlineLabel() ?? '-' }}</flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="6" class="text-center text-zinc-500">Tidak ada SPK yang selesai pada periode ini.</flux:table.cell>
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

        <flux:card class="flex flex-col gap-3">
            <flux:heading size="lg">Detail Rambu ({{ $rambuDetail['total'] }})</flux:heading>
            <flux:subheading>Mengikuti filter Jenis Rambu dan Status Rambu di atas.</flux:subheading>

            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Nomor Surat</flux:table.column>
                    <flux:table.column>Jenis Rambu</flux:table.column>
                    <flux:table.column>Lokasi</flux:table.column>
                    <flux:table.column>Tanggal</flux:table.column>
                    <flux:table.column align="end">Status</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse ($rambuDetail['items'] as $item)
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
