    @php
        use App\Enums\StatusRambuPasang;
        use Illuminate\Support\Facades\Storage;
    @endphp

    <div class="flex w-full flex-1 flex-col gap-6">
        <div class="flex items-end justify-between">
            <div>
                <flux:heading size="xl">Detail Validasi: {{ $spk->nomor_surat }}</flux:heading>
                <flux:subheading>{{ $spk->wilayah }}</flux:subheading>
            </div>

            <flux:button variant="ghost" :href="route('admin.validasi.index')" wire:navigate>Kembali</flux:button>
        </div>

        @if (! $showPenolakanForm)
            <div class="flex flex-col gap-4">
                <div>
                    <flux:heading size="lg">Laporan Akhir Masuk</flux:heading>
                    <flux:subheading>Rambu yang selesai dikerjakan maupun yang terkendala sama-sama perlu dicentang kalau memang sudah bisa dianggap selesai. Yang tidak dicentang akan diminta revisi/dikerjakan ulang.</flux:subheading>
                </div>

                @foreach ($pending as $rp)
                    @php $isKendala = $rp->status === StatusRambuPasang::Tertunda; @endphp
                    <flux:card wire:key="rp-{{ $rp->id }}" class="flex flex-col gap-0 overflow-hidden p-0">
                        @if ($isKendala)
                            @php $kendala = $rp->kendala->first(); @endphp
                            <div class="relative aspect-video bg-zinc-100">
                                @if ($kendala?->foto)
                                    <img src="{{ Storage::url($kendala->foto) }}" class="size-full object-cover" />
                                @else
                                    <x-photo-placeholder class="size-full" label="Belum ada foto" />
                                @endif
                                <span class="absolute top-3 left-3 rounded-full bg-amber-600/85 px-3 py-1 text-xs font-bold tracking-wide text-white uppercase">
                                    Terkendala
                                </span>
                            </div>
                        @else
                            @php $laporan = $rp->laporanPengerjaan->first(); @endphp
                            <div class="grid grid-cols-1 sm:grid-cols-2">
                                <div class="relative aspect-video bg-zinc-100">
                                    @if ($rp->foto_survei)
                                        <img src="{{ Storage::url($rp->foto_survei) }}" class="size-full object-cover" />
                                    @else
                                        <x-photo-placeholder class="size-full" label="Belum ada foto" />
                                    @endif
                                    <span class="absolute top-3 left-3 rounded-full bg-black/60 px-3 py-1 text-xs font-bold tracking-wide text-white uppercase">
                                        Sebelum
                                    </span>
                                </div>
                                <div class="relative aspect-video bg-zinc-100">
                                    @if ($laporan?->foto_sesudah)
                                        <img src="{{ Storage::url($laporan->foto_sesudah) }}" class="size-full object-cover" />
                                    @else
                                        <x-photo-placeholder class="size-full" label="Belum ada foto" />
                                    @endif
                                    <span class="absolute top-3 left-3 rounded-full bg-[#004655]/85 px-3 py-1 text-xs font-bold tracking-wide text-white uppercase">
                                        Sesudah
                                    </span>
                                </div>
                            </div>
                        @endif

                        <div class="flex flex-col gap-3 p-5">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <flux:heading size="sm">{{ $rp->rambu->wilayah }}, {{ $rp->rambu->lokasi }}</flux:heading>
                                    <flux:subheading>
                                        {{ $rp->jenis_pekerjaan->label() }} &middot; {{ $rp->rambu->jenisRambu?->nama_jenis }}
                                        &middot; Dilaporkan oleh {{ ($isKendala ? $kendala?->pelapor : $laporan?->pelapor)?->name }}
                                    </flux:subheading>
                                </div>
                                <flux:checkbox wire:model="checked.{{ $rp->id }}" label="Sesuai" />
                            </div>

                            @if ($isKendala)
                                <flux:callout variant="warning" icon="exclamation-triangle" heading="Kendala yang dilaporkan">
                                    {{ $kendala?->alasan }}
                                </flux:callout>
                            @else
                                @if ($laporan?->catatan_lapangan)
                                    <flux:text class="text-sm text-zinc-600">{{ $laporan->catatan_lapangan }}</flux:text>
                                @endif

                                @if ($laporan?->koordinat_gps)
                                    <flux:text class="text-sm text-zinc-500">Koordinat GPS: {{ $laporan->koordinat_gps }}</flux:text>
                                @endif

                                @if ($laporan?->barangBahan->isNotEmpty())
                                    <div>
                                        <flux:text class="text-sm text-zinc-500">Barang/Bahan:</flux:text>
                                        <ul class="list-inside list-disc text-sm">
                                            @foreach ($laporan->barangBahan as $bb)
                                                <li>{{ $bb->nama }}: {{ $bb->jumlah }} {{ $bb->satuan }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif
                            @endif
                        </div>
                    </flux:card>
                @endforeach

                <div class="flex justify-end">
                    <flux:button variant="primary" wire:click="lanjutkan">Proses Validasi</flux:button>
                </div>
            </div>
        @else
            <flux:card class="flex flex-col gap-4">
                <flux:heading size="lg">Form Penolakan</flux:heading>
                <flux:subheading>Rambu berikut tidak dicentang sesuai. Jelaskan apa yang salah/perlu dikerjakan ulang untuk masing-masing.</flux:subheading>

                @foreach ($pending as $rp)
                    @if (array_key_exists($rp->id, $catatanPenolakan))
                        @php
                            $isKendala = $rp->status === StatusRambuPasang::Tertunda;
                            $foto = $isKendala ? $rp->kendala->first()?->foto : $rp->laporanPengerjaan->first()?->foto_sesudah;
                        @endphp
                        <div wire:key="reject-{{ $rp->id }}" class="flex gap-4 rounded-lg border border-red-200 bg-red-50 p-4">
                            <div class="h-20 w-28 shrink-0 overflow-hidden rounded-lg bg-zinc-100">
                                @if ($foto)
                                    <img src="{{ Storage::url($foto) }}" class="size-full object-cover" />
                                @else
                                    <x-photo-placeholder class="size-full" />
                                @endif
                            </div>
                            <div class="flex flex-1 flex-col gap-2">
                                <flux:heading size="sm">{{ $rp->rambu->wilayah }}, {{ $rp->rambu->lokasi }}</flux:heading>
                                <flux:textarea wire:model="catatanPenolakan.{{ $rp->id }}" label="Catatan Penolakan" placeholder="Jelaskan bagian mana yang salah/perlu dikerjakan ulang" rows="3" required />
                            </div>
                        </div>
                    @endif
                @endforeach

                <div class="flex justify-end gap-3">
                    <flux:button type="button" variant="ghost" wire:click="kembali">Kembali</flux:button>
                    <flux:button variant="danger" wire:click="konfirmasiPenolakan">Konfirmasi & Selesaikan</flux:button>
                </div>
            </flux:card>
        @endif
    </div>
