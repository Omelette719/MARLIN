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

            <div class="flex flex-col items-end gap-1">
                <div class="flex items-center gap-3">
                    <flux:button variant="ghost" icon="arrow-down-tray" :href="route('spk.surat-pengantar', $spk)" target="_blank">
                        Unduh Surat Pengantar
                    </flux:button>
                    <flux:button variant="ghost" :href="route('dashboard')" wire:navigate>Kembali</flux:button>
                </div>
                <flux:text class="text-xs text-zinc-500">Serahkan surat ini ke RT/perwakilan warga untuk diisi dan ditandatangani secara manual.</flux:text>
            </div>
        </div>

        <flux:card class="flex flex-col gap-3">
            <flux:heading size="lg">Detail Surat</flux:heading>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
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
                    <flux:text class="text-sm text-zinc-500">Asal Permintaan</flux:text>
                    <flux:text>{{ $spk->asal_permintaan->label() }}</flux:text>
                </div>
            </div>

            @if ($spk->catatan_pekerja_tambahan)
                <div>
                    <flux:text class="text-sm text-zinc-500">Catatan</flux:text>
                    <flux:text>{{ $spk->catatan_pekerja_tambahan }}</flux:text>
                </div>
            @endif
        </flux:card>

        <flux:card class="flex flex-col gap-4">
            <flux:heading size="lg">Tim Bertugas</flux:heading>

            @if ($tim->isEmpty())
                <flux:text class="text-zinc-500">
                    Surat ini belum didaftarkan timnya. Kalau kamu yang akan mengerjakan, daftarkan dirimu sebagai perwakilan beserta anggota timmu.
                </flux:text>

                <form wire:submit="daftarkanTim" class="flex flex-col gap-3 rounded-lg border border-zinc-200 bg-zinc-50 p-4">
                    <x-searchable-multiselect
                        wire-model="anggotaIds"
                        :options="$userOptions"
                        label="Anggota Tim (opsional, kamu otomatis jadi perwakilan)"
                    />
                    <flux:button type="submit" variant="primary" size="sm" class="self-start">Daftarkan Tim</flux:button>
                </form>
            @else
                <ul class="list-inside list-disc">
                    @foreach ($tim as $t)
                        <li>{{ $t->user->name }} @if ($t->is_perwakilan) <flux:badge size="sm">Perwakilan</flux:badge> @endif</li>
                    @endforeach
                </ul>

                @if ($this->sayaPerwakilan)
                    <div class="flex flex-col gap-3 border-t border-zinc-200 pt-3" x-data="{ tambah: false }">
                        <flux:badge color="blue" size="sm" class="w-fit">Kamu perwakilan untuk surat ini</flux:badge>

                        <flux:button type="button" variant="ghost" size="sm" class="self-start" x-show="! tambah" x-on:click="tambah = true">
                            + Tambah Anggota
                        </flux:button>

                        <form wire:submit="tambahAnggota" x-show="tambah" x-cloak class="flex flex-col gap-3">
                            <x-searchable-multiselect wire-model="anggotaIds" :options="$userOptions" label="Anggota Baru" />
                            <flux:button type="submit" variant="primary" size="sm" class="self-start">Tambahkan</flux:button>
                        </form>
                    </div>
                @elseif ($this->sudahBergabung)
                    <flux:badge color="zinc" size="sm" class="w-fit">Kamu bagian dari tim ini</flux:badge>
                @else
                    <flux:text class="text-sm text-zinc-500">
                        Hanya perwakilan ({{ $perwakilan->user->name ?? '-' }}) yang bisa mendaftarkan/menambah anggota dan mengisi kendala/laporan.
                    </flux:text>
                @endif
            @endif
        </flux:card>

        <flux:card class="flex flex-col gap-3">
            <flux:heading size="lg">Laporan Akhir</flux:heading>

            @if ($spk->laporan_akhir_diajukan_at)
                <flux:badge color="cyan" size="sm" class="w-fit">Laporan akhir sudah diajukan, menunggu validasi admin</flux:badge>
            @elseif ($this->sayaPerwakilan)
                @if ($this->siapDiajukan)
                    <flux:text class="text-sm text-zinc-500">Semua rambu sudah ditangani (selesai atau terkendala). Surat ini siap diajukan sebagai laporan akhir.</flux:text>
                    <flux:button variant="primary" size="sm" class="self-start" wire:click="ajukanLaporanAkhir" wire:confirm="Ajukan laporan akhir untuk surat ini?">
                        Ajukan Laporan Akhir
                    </flux:button>
                @else
                    <flux:text class="text-sm text-zinc-500">Selesaikan atau ajukan kendala untuk semua rambu di bawah dulu sebelum bisa mengajukan laporan akhir.</flux:text>
                @endif
            @else
                <flux:text class="text-sm text-zinc-500">Hanya perwakilan yang bisa mengajukan laporan akhir.</flux:text>
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
                                    <flux:heading size="sm">{{ $rp->jenis_pekerjaan->label() }}</flux:heading>
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

                            <div class="mt-auto flex gap-2 pt-2">
                                <flux:button size="sm" variant="ghost" icon="map" class="flex-1" :href="route('peta', ['focus' => $rp->rambu_id])" wire:navigate>
                                    Peta
                                </flux:button>
                                @if ($rp->rambu->koordinat && str_contains($rp->rambu->koordinat, ','))
                                    <flux:button size="sm" variant="ghost" icon="arrow-top-right-on-square" :href="'https://www.google.com/maps/search/?api=1&query='.$rp->rambu->koordinat" target="_blank" />
                                @endif
                            </div>

                            @if ($this->sayaPerwakilan && in_array($rp->status->value, $workableStatuses))
                                <div class="flex gap-2 border-t border-zinc-200 pt-2">
                                    <flux:button size="sm" variant="ghost" class="flex-1" :href="route('user.kendala', ['rambuPasangId' => $rp->id])" wire:navigate>Kendala</flux:button>
                                    <flux:button size="sm" variant="primary" class="flex-1" :href="route('user.laporan', ['rambuPasangId' => $rp->id])" wire:navigate>Laporan</flux:button>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </flux:card>
    </div>
