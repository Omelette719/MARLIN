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
                    @php $jenisRingkasan = $spk->jenisRingkasan(); @endphp
                    <flux:badge size="sm" :color="match (true) {
                        $jenisRingkasan === null => 'violet',
                        $jenisRingkasan === JenisPekerjaan::Perbaikan => 'amber',
                        default => 'cyan',
                    }">
                        {{ $jenisRingkasan?->label() ?? 'Campuran' }}
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
                @if ($spk->status === \App\Enums\StatusSpk::Aktif)
                    <flux:button variant="ghost" icon="pencil" :href="route('admin.spk.edit', $spk)" wire:navigate>
                        Edit
                    </flux:button>
                    <flux:modal.trigger name="batalkan-spk">
                        <flux:button variant="danger" icon="x-circle">
                            Batalkan SPK
                        </flux:button>
                    </flux:modal.trigger>
                @endif
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
                    <flux:badge size="sm" :color="match ($spk->urgensiSaatIni()) {
                        Urgensi::Tinggi => 'red',
                        Urgensi::Sedang => 'amber',
                        Urgensi::Rendah => 'zinc',
                    }">{{ $spk->urgensiSaatIni()->label() }}</flux:badge>
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

            @if ($spk->tanggal_survei)
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <flux:text class="text-sm text-zinc-500">Tanggal Survei</flux:text>
                        <flux:text>{{ $spk->tanggal_survei->translatedFormat('d M Y') }}</flux:text>
                    </div>
                    <div>
                        <flux:text class="text-sm text-zinc-500">Petugas Survei</flux:text>
                        <flux:text>{{ $spk->petugas_survei }}</flux:text>
                    </div>
                </div>
            @endif

            @if ($contactPerson = $spk->rtPerwakilan->first())
                <div>
                    <flux:text class="text-sm text-zinc-500">Contact Person</flux:text>
                    <flux:text>{{ $contactPerson->nama_lengkap }}{{ $contactPerson->no_telepon ? ' ('.$contactPerson->no_telepon.')' : '' }}</flux:text>
                </div>
            @endif

            @if ($spk->status === \App\Enums\StatusSpk::Selesai && $spk->selesai_pada)
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <flux:text class="text-sm text-zinc-500">Durasi Pengerjaan</flux:text>
                        <flux:text>{{ $spk->durasiPengerjaanHari() }} hari (dibuat {{ $spk->created_at->translatedFormat('d M Y') }}, selesai {{ $spk->selesai_pada->translatedFormat('d M Y') }})</flux:text>
                    </div>
                    <div>
                        <flux:text class="text-sm text-zinc-500">Selisih dari Deadline</flux:text>
                        <flux:badge size="sm" :color="$spk->selisihDeadlineHari() > 0 ? 'red' : 'green'">{{ $spk->selisihDeadlineLabel() }}</flux:badge>
                    </div>
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
                    <div wire:key="rp-{{ $rp->id }}" class="flex flex-col overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-xs transition hover:shadow-md">
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
                                <div class="flex flex-col items-end gap-1">
                                    <flux:badge size="sm" :color="match ($rp->status) {
                                        StatusRambuPasang::Selesai => 'green',
                                        StatusRambuPasang::MenungguValidasi => 'blue',
                                        StatusRambuPasang::Urgent, StatusRambuPasang::Revisi => 'red',
                                        StatusRambuPasang::Tertunda => 'amber',
                                        StatusRambuPasang::Batal => 'zinc',
                                        default => 'zinc',
                                    }">{{ $rp->status->label() }}</flux:badge>
                                    <flux:badge size="sm" :color="$rp->jenis_pekerjaan === JenisPekerjaan::Perbaikan ? 'amber' : 'cyan'">
                                        {{ $rp->jenis_pekerjaan->label() }}
                                    </flux:badge>
                                </div>
                            </div>

                            @if ($rp->rambu->koordinat)
                                <flux:text class="font-mono text-xs text-zinc-500">{{ $rp->rambu->koordinat }}</flux:text>
                            @endif

                            <flux:text class="text-sm">Jumlah: {{ $rp->jumlah }}</flux:text>

                            @if ($rp->catatan_instruksi)
                                <flux:text class="text-sm text-zinc-500">{{ $rp->catatan_instruksi }}</flux:text>
                            @endif

                            @if ($rp->status === StatusRambuPasang::Batal && $rp->catatan_pembatalan)
                                <flux:text class="text-sm text-red-600">Dibatalkan: {{ $rp->catatan_pembatalan }}</flux:text>
                            @elseif ($rp->status === StatusRambuPasang::Tertunda && $rp->kendala->first())
                                <flux:callout variant="warning" icon="exclamation-triangle" heading="Kendala yang dilaporkan">
                                    {{ $rp->kendala->first()->alasan }}
                                </flux:callout>
                            @endif

                            <div class="mt-auto flex gap-2 pt-2">
                                <flux:button size="sm" variant="ghost" icon="information-circle" class="flex-1" :href="route('rambu.show', $rp->rambu_id)" wire:navigate>
                                    Detail
                                </flux:button>
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

        <x-confirm-modal
            name="batalkan-spk"
            heading="Batalkan SPK ini?"
            text="Rambu yang belum selesai akan ditandai batal. Tindakan ini tidak bisa dibatalkan."
            action="batalkan"
            confirm-label="Ya, Batalkan SPK"
            tone="danger"
        />
    </div>
