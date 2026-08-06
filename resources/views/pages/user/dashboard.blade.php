    @php
        use App\Enums\JenisPekerjaan;
        use App\Enums\StatusRambuPasang;
        use App\Enums\Urgensi;
        use Illuminate\Support\Facades\Storage;
    @endphp

    <div class="flex w-full flex-1 flex-col gap-6">
        <div class="flex items-end justify-between">
            <div>
                <flux:heading size="xl">Daftar Surat Aktif</flux:heading>
                <flux:subheading>Daftar surat pemasangan rambu yang menjadi tanggung jawab petugas lapangan.</flux:subheading>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <flux:card class="flex items-center gap-4">
                <div class="flex size-12 shrink-0 items-center justify-center rounded-full bg-blue-100 text-blue-600">
                    <flux:icon icon="clipboard-document-list" class="size-6" />
                </div>
                <div class="min-w-0">
                    <flux:text class="text-sm text-zinc-500">Aktif</flux:text>
                    <flux:heading size="xl">{{ $aktifCount }}</flux:heading>
                </div>
            </flux:card>

            <flux:card class="flex items-center gap-4">
                <div class="flex size-12 shrink-0 items-center justify-center rounded-full bg-cyan-100 text-cyan-600">
                    <flux:icon icon="arrow-path" class="size-6" />
                </div>
                <div class="min-w-0">
                    <flux:text class="text-sm text-zinc-500">Progres</flux:text>
                    <flux:heading size="xl">{{ $progresCount }}</flux:heading>
                </div>
            </flux:card>

            <flux:card class="flex items-center gap-4">
                <div class="flex size-12 shrink-0 items-center justify-center rounded-full bg-red-100 text-red-600">
                    <flux:icon icon="exclamation-triangle" class="size-6" />
                </div>
                <div class="min-w-0">
                    <flux:text class="text-sm text-zinc-500">Mendekati Deadline</flux:text>
                    <flux:heading size="xl">{{ $mendekatiDeadlineCount }}</flux:heading>
                </div>
            </flux:card>

            <flux:card class="flex items-center gap-4">
                <div class="flex size-12 shrink-0 items-center justify-center rounded-full bg-green-100 text-green-600">
                    <flux:icon icon="check-circle" class="size-6" />
                </div>
                <div class="min-w-0">
                    <flux:text class="text-sm text-zinc-500">Selesai Bulan Ini</flux:text>
                    <flux:heading size="xl">{{ $selesaiBulanIniCount }}</flux:heading>
                </div>
            </flux:card>
        </div>

        <flux:input wire:model.live.debounce.400ms="search" placeholder="Cari nomor surat atau wilayah..." icon="magnifying-glass" class="max-w-sm" />

        @if ($spk->isEmpty())
            <flux:card class="flex-1">
                <flux:text class="py-8 text-center text-zinc-500">Belum ada surat aktif saat ini.</flux:text>
            </flux:card>
        @else
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($spk as $item)
                    <div wire:key="spk-{{ $item->id }}" class="flex flex-col overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-xs transition hover:shadow-md {{ $joinedSpkIds->contains($item->id) ? 'ring-2 ring-blue-400' : '' }}">
                        <x-photo-slideshow :photos="$item->cover_photos->map(fn ($p) => Storage::url($p))" class="aspect-video w-full" />

                        <div class="flex flex-1 flex-col gap-2 p-4">
                            <div class="flex items-start justify-between gap-2">
                                <flux:heading size="sm">{{ $item->nomor_surat }}</flux:heading>
                                <flux:badge size="sm" :color="$item->jenis_spk === JenisPekerjaan::Perbaikan ? 'amber' : 'cyan'">
                                    {{ $item->jenis_spk->label() }}
                                </flux:badge>
                            </div>

                            <flux:text class="text-sm text-zinc-500">{{ $item->wilayah }}</flux:text>

                            <div class="flex flex-wrap items-center gap-2">
                                @if ($item->prioritas)
                                    <flux:badge color="red" size="sm">Prioritas</flux:badge>
                                @endif
                                <flux:badge size="sm" :color="match ($item->urgensi) {
                                    Urgensi::Tinggi => 'red',
                                    Urgensi::Sedang => 'amber',
                                    Urgensi::Rendah => 'zinc',
                                }">{{ $item->urgensi->label() }}</flux:badge>
                                <flux:badge size="sm" :color="match ($item->progress_status) {
                                    StatusRambuPasang::Selesai => 'green',
                                    StatusRambuPasang::MenungguValidasi => 'blue',
                                    StatusRambuPasang::Urgent, StatusRambuPasang::Revisi => 'red',
                                    StatusRambuPasang::Tertunda => 'amber',
                                    default => 'zinc',
                                }">{{ $item->progress_status->label() }}</flux:badge>
                                @if ($joinedSpkIds->contains($item->id))
                                    <flux:badge color="blue" variant="solid" size="sm">Sudah Bergabung</flux:badge>
                                @else
                                    <flux:badge color="zinc" size="sm">Belum Bergabung</flux:badge>
                                @endif
                            </div>

                            <flux:text class="text-sm text-zinc-500">
                                Deadline {{ $item->deadline->translatedFormat('d M Y') }}, {{ $item->rambu_pasang_count }} unit rambu
                            </flux:text>

                            <div class="mt-auto flex gap-2 pt-2">
                                <flux:button size="sm" variant="primary" class="flex-1" :href="route('user.spk.show', $item)" wire:navigate>
                                    Lihat Detail
                                </flux:button>
                                <flux:button size="sm" variant="ghost" icon="arrow-down-tray" :href="route('spk.surat-pengantar', $item)" target="_blank" />
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div>
                {{ $spk->links() }}
            </div>
        @endif
    </div>
