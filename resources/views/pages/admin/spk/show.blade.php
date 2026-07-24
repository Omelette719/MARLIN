    @php
        use App\Enums\JenisPekerjaan;
        use App\Enums\StatusRambuPasang;
        use App\Enums\Urgensi;
        use Illuminate\Support\Facades\Storage;
    @endphp

    <div class="flex w-full flex-1 flex-col gap-6">
        <div class="flex items-end justify-between">
            <div>
                <flux:heading size="xl">
                    {{ $spk->nomor_surat }}
                    <flux:badge size="sm" :color="$spk->jenis_spk === JenisPekerjaan::Perbaikan ? 'amber' : 'cyan'">
                        {{ $spk->jenis_spk->label() }}
                    </flux:badge>
                    @if ($spk->prioritas)
                        <flux:badge color="red" size="sm">Prioritas</flux:badge>
                    @endif
                </flux:heading>
                <flux:subheading>{{ $spk->wilayah }}</flux:subheading>
            </div>

            <div class="flex items-center gap-3">
                <flux:button variant="ghost" icon="arrow-down-tray" :href="route('spk.surat-pengantar', $spk)" target="_blank">
                    Unduh Surat Pengantar
                </flux:button>
                <flux:button variant="ghost" :href="route('admin.spk.index')" wire:navigate>Kembali</flux:button>
            </div>
        </div>

        <flux:card class="flex flex-col gap-3">
            <flux:heading size="lg">Detail Surat</flux:heading>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-4">
                <div>
                    <flux:text class="text-sm text-zinc-500">Deadline</flux:text>
                    <flux:text>{{ $spk->deadline->translatedFormat('d M Y') }}</flux:text>
                </div>
                <div>
                    <flux:text class="text-sm text-zinc-500">Urgensi</flux:text>
                    <flux:badge size="sm" :color="match ($spk->urgensi) {
                        Urgensi::Tinggi => 'red',
                        Urgensi::Sedang => 'amber',
                        Urgensi::Rendah => 'zinc',
                    }">{{ $spk->urgensi->label() }}</flux:badge>
                </div>
                <div>
                    <flux:text class="text-sm text-zinc-500">Status</flux:text>
                    <flux:badge size="sm" :color="match ($spk->status) {
                        \App\Enums\StatusSpk::Selesai => 'green',
                        \App\Enums\StatusSpk::Aktif => 'blue',
                        \App\Enums\StatusSpk::Dibatalkan => 'zinc',
                    }">{{ $spk->status->label() }}</flux:badge>
                </div>
                <div>
                    <flux:text class="text-sm text-zinc-500">Asal Permintaan</flux:text>
                    <flux:text>{{ $spk->asal_permintaan->label() }}</flux:text>
                </div>
            </div>

            @if ($spk->keterangan_asal)
                <div>
                    <flux:text class="text-sm text-zinc-500">Keterangan Asal</flux:text>
                    <flux:text>{{ $spk->keterangan_asal }}</flux:text>
                </div>
            @endif

            @if ($spk->catatan_pekerja_tambahan)
                <div>
                    <flux:text class="text-sm text-zinc-500">Catatan</flux:text>
                    <flux:text>{{ $spk->catatan_pekerja_tambahan }}</flux:text>
                </div>
            @endif

            @if ($spk->file_referensi)
                <div>
                    <flux:button size="sm" variant="ghost" icon="paper-clip" href="{{ Storage::url($spk->file_referensi) }}" target="_blank">
                        Lihat File Referensi
                    </flux:button>
                </div>
            @endif
        </flux:card>

        <flux:card class="flex flex-col gap-3">
            <flux:heading size="lg">Tim Bertugas</flux:heading>

            @if ($tim->isEmpty())
                <flux:text class="text-zinc-500">Belum ada petugas yang bergabung.</flux:text>
            @else
                <ul class="list-inside list-disc">
                    @foreach ($tim as $t)
                        <li>{{ $t->user->name }} @if ($t->is_perwakilan) <flux:badge size="sm">Perwakilan</flux:badge> @endif</li>
                    @endforeach
                </ul>
            @endif
        </flux:card>

        <flux:card class="flex flex-col gap-3">
            <flux:heading size="lg">Daftar Rambu</flux:heading>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($rambuPasang as $rp)
                    <div wire:key="rp-{{ $rp->id }}" class="flex flex-col overflow-hidden rounded-xl border border-zinc-200">
                        <div class="aspect-video w-full overflow-hidden bg-zinc-100">
                            @if ($rp->foto_survei)
                                <img src="{{ Storage::url($rp->foto_survei) }}" class="size-full object-cover" />
                            @else
                                <x-photo-placeholder class="size-full" />
                            @endif
                        </div>

                        <div class="flex flex-col gap-2 p-4">
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <flux:heading size="sm">{{ $rp->rambu->jenisRambu?->nama_jenis }}</flux:heading>
                                    <flux:text class="text-sm text-zinc-500">{{ $rp->rambu->wilayah }}, {{ $rp->rambu->lokasi }}</flux:text>
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

                            @if ($rp->rambu->koordinat)
                                <flux:text class="font-mono text-xs text-zinc-500">{{ $rp->rambu->koordinat }}</flux:text>
                            @endif

                            <flux:text class="text-sm">Jumlah: {{ $rp->jumlah }}</flux:text>

                            @if ($rp->catatan_instruksi)
                                <flux:text class="text-sm text-zinc-500">{{ $rp->catatan_instruksi }}</flux:text>
                            @endif

                            <div class="mt-auto flex gap-2 pt-2">
                                <flux:button size="sm" variant="ghost" icon="map" class="flex-1" :href="route('peta', ['focus' => $rp->rambu_id])" wire:navigate>
                                    Peta
                                </flux:button>
                                @if ($rp->rambu->koordinat && str_contains($rp->rambu->koordinat, ','))
                                    <flux:button size="sm" variant="ghost" icon="arrow-top-right-on-square" :href="'https://www.google.com/maps/search/?api=1&query='.$rp->rambu->koordinat" target="_blank" />
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </flux:card>
    </div>
