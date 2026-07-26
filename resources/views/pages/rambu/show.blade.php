    @php
        use App\Enums\KondisiRambu;
        use App\Enums\StatusRambuPasang;
        use Illuminate\Support\Facades\Storage;
    @endphp

    <div class="flex w-full flex-1 flex-col gap-6">
        <div class="flex items-end justify-between">
            <div>
                <flux:heading size="xl">Detail Rambu</flux:heading>
                <flux:subheading>{{ $rambu->jenisRambu?->nama_jenis }} &middot; {{ $rambu->wilayah }}</flux:subheading>
            </div>

            <flux:button variant="ghost" icon="arrow-left" :href="$isAdmin ? route('admin.rambu.index') : route('rambu.index')" wire:navigate>
                Kembali
            </flux:button>
        </div>

        <flux:card class="flex flex-col gap-0 overflow-hidden p-0">
            <div class="relative aspect-video bg-zinc-100">
                @if ($foto)
                    <img src="{{ Storage::url($foto) }}" class="size-full object-cover" />
                @else
                    <x-photo-placeholder class="size-full" label="Belum ada foto" />
                @endif
            </div>

            <div class="flex flex-col gap-4 p-6">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <flux:text class="text-sm text-zinc-500">Jenis Rambu</flux:text>
                        <flux:heading size="sm">{{ $rambu->jenisRambu?->nama_jenis ?? '-' }}</flux:heading>
                    </div>
                    <div>
                        <flux:text class="text-sm text-zinc-500">Wilayah</flux:text>
                        <flux:heading size="sm">{{ $rambu->wilayah }}</flux:heading>
                    </div>
                    <div>
                        <flux:text class="text-sm text-zinc-500">Lokasi</flux:text>
                        <flux:heading size="sm">{{ $rambu->lokasi }}</flux:heading>
                    </div>
                    <div>
                        <flux:text class="text-sm text-zinc-500">Koordinat</flux:text>
                        <flux:heading size="sm" class="font-mono">{{ $rambu->koordinat ?: '-' }}</flux:heading>
                    </div>
                    <div>
                        <flux:text class="mb-1 text-sm text-zinc-500">Kondisi Terkini</flux:text>
                        <flux:badge size="sm" :color="$rambu->kondisi_terkini === KondisiRambu::Rusak ? 'red' : 'green'">
                            {{ $rambu->kondisi_terkini->label() }}
                        </flux:badge>
                    </div>
                    <div>
                        <flux:text class="mb-1 text-sm text-zinc-500">Status Pemasangan</flux:text>
                        <flux:badge size="sm" :color="$rambu->sudah_terpasang ? 'blue' : 'zinc'">
                            {{ $rambu->sudah_terpasang ? 'Terpasang' : 'Belum Terpasang' }}
                        </flux:badge>
                    </div>
                </div>

                @if ($rambu->jenisRambu?->spesifikasi_standar)
                    <div>
                        <flux:text class="text-sm text-zinc-500">Spesifikasi Standar</flux:text>
                        <flux:text>{{ $rambu->jenisRambu->spesifikasi_standar }}</flux:text>
                    </div>
                @endif

                <div class="flex flex-wrap gap-3">
                    <flux:button variant="primary" icon="map" :href="route('peta', ['focus' => $rambu->id])" wire:navigate>
                        Lihat di Peta
                    </flux:button>

                    @if ($googleMapsUrl)
                        <flux:button variant="ghost" icon="arrow-top-right-on-square" :href="$googleMapsUrl" target="_blank">
                            Buka di Google Maps
                        </flux:button>
                    @endif
                </div>
            </div>
        </flux:card>

        <flux:card class="flex flex-col gap-4">
            <flux:heading size="lg">Riwayat Pekerjaan</flux:heading>

            @forelse ($riwayat as $rp)
                <div wire:key="riwayat-{{ $rp->id }}" class="flex flex-col gap-2 rounded-lg border border-zinc-200 p-4">
                    <div class="flex items-center justify-between">
                        <flux:link
                            :href="route($isAdmin ? 'admin.spk.show' : 'user.spk.show', $rp->spk)"
                            wire:navigate
                            class="font-semibold"
                        >
                            {{ $rp->spk->nomor_surat }}
                        </flux:link>
                        <flux:badge size="sm">{{ $rp->status->label() }}</flux:badge>
                    </div>
                    <flux:subheading>{{ $rp->jenis_pekerjaan->label() }} &middot; Deadline {{ $rp->spk->deadline->format('d M Y') }}</flux:subheading>
                    @if ($rp->catatan_instruksi)
                        <flux:text class="text-sm text-zinc-600">{{ $rp->catatan_instruksi }}</flux:text>
                    @endif

                    @if ($isAdmin && in_array($rp->status, [StatusRambuPasang::MenungguValidasi, StatusRambuPasang::Tertunda], true))
                        <flux:button
                            size="sm"
                            variant="primary"
                            icon="clipboard-document-check"
                            :href="route('admin.validasi.show', $rp->spk)"
                            wire:navigate
                            class="self-start"
                        >
                            Ke Halaman Validasi
                        </flux:button>
                    @endif
                </div>
            @empty
                <flux:text class="text-zinc-500">Belum ada riwayat pekerjaan untuk rambu ini.</flux:text>
            @endforelse
        </flux:card>
    </div>
