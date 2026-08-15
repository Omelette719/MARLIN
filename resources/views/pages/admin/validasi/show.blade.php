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

            <x-back-button :fallback="route('admin.validasi.index')">Kembali</x-back-button>
        </div>

        <flux:card class="flex flex-col gap-3">
            <div>
                <flux:heading size="lg">Semua Rambu dalam SPK Ini</flux:heading>
                <flux:subheading>Termasuk rambu yang sudah divalidasi di putaran sebelumnya — hanya yang berstatus Terkendala/Menunggu Validasi di bawah yang perlu keputusan sekarang.</flux:subheading>
            </div>

            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($semua as $rp)
                    <div wire:key="semua-{{ $rp->id }}" class="flex items-center justify-between gap-2 rounded-lg border border-zinc-200 p-3">
                        <div class="min-w-0">
                            <flux:text class="truncate text-sm font-medium text-zinc-700">{{ $rp->rambu->wilayah }}, {{ $rp->rambu->lokasi }}</flux:text>
                            <flux:text class="text-xs text-zinc-500">{{ $rp->rambu->jenisRambu?->nama_jenis }}</flux:text>
                        </div>
                        <flux:badge size="sm" :color="match ($rp->status) {
                            StatusRambuPasang::Selesai => 'green',
                            StatusRambuPasang::MenungguValidasi => 'blue',
                            StatusRambuPasang::Urgent, StatusRambuPasang::Revisi => 'red',
                            StatusRambuPasang::Tertunda => 'amber',
                            StatusRambuPasang::Batal => 'zinc',
                            default => 'zinc',
                        }">{{ $rp->status->label() }}</flux:badge>
                    </div>
                @endforeach
            </div>
        </flux:card>

        @if (! $showPenolakanForm)
            <div class="flex flex-col gap-4">
                <div>
                    <flux:heading size="lg">Laporan Akhir Masuk</flux:heading>
                    <flux:subheading>Rambu yang selesai dikerjakan bisa dicentang kalau memang sudah sesuai. Rambu yang terkendala tidak bisa dicentang selesai — otomatis dikembalikan untuk direvisi, karena belum ada pekerjaan yang benar-benar selesai untuk divalidasi.</flux:subheading>
                </div>

                @foreach ($pending as $rp)
                    @php
                        $isKendala = $rp->status === StatusRambuPasang::Tertunda;
                        // Kendala never checkable: a kendala report exists precisely
                        // because the work COULDN'T be completed, so there's no
                        // finished work to accept — it always needs to go through
                        // the rejection/revision path below instead.
                        $isChecked = ! $isKendala && ($checked[$rp->id] ?? false);
                    @endphp
                    <div
                        wire:key="rp-{{ $rp->id }}"
                        @unless ($isKendala) wire:click="$toggle('checked.{{ $rp->id }}')" @endunless
                        role="{{ $isKendala ? 'group' : 'checkbox' }}"
                        @unless ($isKendala) aria-checked="{{ $isChecked ? 'true' : 'false' }}" tabindex="0" @endunless
                        class="flex flex-col overflow-hidden rounded-2xl border-2 bg-white shadow-xs transition hover:shadow-md {{ $isKendala ? 'cursor-default border-amber-300' : 'cursor-pointer '.($isChecked ? 'border-green-400' : 'border-transparent') }}"
                    >
                        <div class="relative">
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

                            @unless ($isKendala)
                                <div class="absolute top-3 right-3 flex size-9 items-center justify-center rounded-full shadow transition {{ $isChecked ? 'bg-green-500 text-white' : 'bg-white/90 text-transparent' }}">
                                    <flux:icon icon="check" class="size-5" />
                                </div>
                            @endunless
                        </div>

                        <div class="flex flex-col gap-3 p-5">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <flux:heading size="sm">{{ $rp->rambu->wilayah }}, {{ $rp->rambu->lokasi }}</flux:heading>
                                    <flux:subheading>
                                        {{ $rp->jenis_pekerjaan->label() }} &middot; {{ $rp->rambu->jenisRambu?->nama_jenis }}
                                        &middot; Dilaporkan oleh {{ ($isKendala ? $kendala?->pelapor : $laporan?->pelapor)?->name }}
                                    </flux:subheading>
                                </div>
                                @if ($isKendala)
                                    <flux:badge size="sm" color="amber">Akan dikembalikan untuk direvisi</flux:badge>
                                @else
                                    <flux:badge size="sm" :color="$isChecked ? 'green' : 'zinc'">
                                        {{ $isChecked ? 'Sesuai' : 'Klik untuk tandai sesuai' }}
                                    </flux:badge>
                                @endif
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
                    </div>
                @endforeach

                <div class="flex justify-end">
                    <flux:modal.trigger name="proses-validasi">
                        <flux:button variant="primary">Proses Validasi</flux:button>
                    </flux:modal.trigger>
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

                <div class="rounded-lg border border-blue-200 bg-blue-50 p-4">
                    <flux:checkbox wire:model.live="ubahDeadline" label="Beri kelonggaran, perpanjang deadline SPK ini juga" description="Wajar kalau revisi butuh waktu tambahan. Berlaku untuk seluruh SPK, bukan cuma rambu yang direvisi." />

                    @if ($ubahDeadline)
                        <div class="mt-3 flex flex-wrap items-end gap-3">
                            <flux:input type="date" wire:model="deadlineBaru" label="Deadline Baru" class="max-w-xs" />
                            <flux:text class="pb-2 text-sm text-zinc-500">Deadline saat ini: {{ $spk->deadline->translatedFormat('d M Y') }}</flux:text>
                        </div>
                    @endif
                </div>

                <div class="flex justify-end gap-3">
                    <flux:button type="button" variant="ghost" wire:click="kembali">Kembali</flux:button>
                    <flux:modal.trigger name="konfirmasi-penolakan">
                        <flux:button variant="danger">Konfirmasi & Selesaikan</flux:button>
                    </flux:modal.trigger>
                </div>
            </flux:card>
        @endif

        <x-confirm-modal
            name="proses-validasi"
            heading="Lanjutkan proses validasi?"
            text="Rambu yang dicentang akan langsung disetujui dan tidak bisa dibatalkan."
            action="lanjutkan"
            confirm-label="Ya, Lanjutkan"
            tone="primary"
        />

        <x-confirm-modal
            name="konfirmasi-penolakan"
            heading="Konfirmasi hasil validasi ini?"
            text="Rambu yang ditolak akan dikembalikan ke petugas untuk direvisi. Tindakan ini tidak bisa dibatalkan."
            action="konfirmasiPenolakan"
            confirm-label="Ya, Konfirmasi"
            tone="danger"
        />
    </div>
