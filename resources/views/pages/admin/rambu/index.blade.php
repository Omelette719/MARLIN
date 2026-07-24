    @php
        use App\Enums\KondisiRambu;
    @endphp

    <div class="flex w-full flex-1 flex-col gap-6">
        <div class="flex items-end justify-between">
            <div>
                <flux:heading size="xl">Daftar Rambu{{ $jenisAktif ? ': '.$jenisAktif->nama_jenis : '' }}</flux:heading>
                <flux:subheading>Daftar aset fisik rambu lalu lintas yang tercatat di sistem.</flux:subheading>
            </div>

            <flux:button variant="ghost" icon="arrow-left" :href="$isAdmin ? route('admin.jenis-rambu.index') : route('jenis-rambu.index')" wire:navigate>
                Kembali ke Jenis Rambu
            </flux:button>
        </div>

        <div class="flex items-center gap-4">
            <flux:input wire:model.live.debounce.400ms="search" placeholder="Cari wilayah atau lokasi..." icon="magnifying-glass" class="max-w-sm" />

            <flux:select wire:model.live="jenis" placeholder="Semua Jenis" class="max-w-xs">
                <flux:select.option value="">Semua Jenis</flux:select.option>
                @foreach ($jenisOptions as $j)
                    <flux:select.option value="{{ $j->id }}">{{ $j->nama_jenis }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>

        <flux:card class="flex-1">
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Jenis Rambu</flux:table.column>
                    <flux:table.column>Wilayah</flux:table.column>
                    <flux:table.column>Lokasi</flux:table.column>
                    <flux:table.column>Koordinat</flux:table.column>
                    <flux:table.column>Kondisi</flux:table.column>
                    <flux:table.column>Status</flux:table.column>
                    <flux:table.column align="end">Aksi</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse ($rambu as $r)
                        <flux:table.row>
                            <flux:table.cell>{{ $r->jenisRambu?->nama_jenis }}</flux:table.cell>
                            <flux:table.cell>{{ $r->wilayah }}</flux:table.cell>
                            <flux:table.cell>{{ $r->lokasi }}</flux:table.cell>
                            <flux:table.cell class="font-mono text-xs">{{ $r->koordinat }}</flux:table.cell>
                            <flux:table.cell>
                                <flux:badge size="sm" :color="$r->kondisi_terkini === KondisiRambu::Rusak ? 'red' : 'green'">
                                    {{ $r->kondisi_terkini->label() }}
                                </flux:badge>
                            </flux:table.cell>
                            <flux:table.cell>
                                <flux:badge size="sm" :color="$r->sudah_terpasang ? 'blue' : 'zinc'">
                                    {{ $r->sudah_terpasang ? 'Terpasang' : 'Belum Terpasang' }}
                                </flux:badge>
                            </flux:table.cell>
                            <flux:table.cell align="end">
                                <div class="flex justify-end gap-2">
                                    <flux:button size="sm" variant="ghost" icon="eye" :href="route('rambu.show', $r)" wire:navigate>
                                        Detail
                                    </flux:button>
                                    <flux:button size="sm" variant="ghost" icon="map" :href="route('peta', ['focus' => $r->id])" wire:navigate>
                                        Peta
                                    </flux:button>
                                    @if ($r->koordinat && str_contains($r->koordinat, ','))
                                        <flux:button size="sm" variant="ghost" icon="arrow-top-right-on-square" :href="'https://www.google.com/maps/search/?api=1&query='.$r->koordinat" target="_blank" />
                                    @endif
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="7" class="text-center text-zinc-500">
                                Tidak ada rambu ditemukan.
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>

            <div class="mt-4">
                {{ $rambu->links() }}
            </div>
        </flux:card>
    </div>
