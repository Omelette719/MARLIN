    @php
        use App\Enums\JenisPekerjaan;
        use App\Enums\StatusSpk;
        use App\Enums\Urgensi;
        use Illuminate\Support\Facades\Storage;
    @endphp

    <div class="flex w-full flex-1 flex-col gap-6">
        <div class="flex items-end justify-between">
            <div>
                <flux:heading size="xl">Daftar Surat</flux:heading>
                <flux:subheading>Daftar Surat Perintah Kerja (SPK) pemasangan &amp; perbaikan rambu</flux:subheading>
            </div>

            <flux:button variant="primary" icon="plus" :href="route('admin.spk.create')" wire:navigate>
                Buat Surat
            </flux:button>
        </div>

        <div class="flex items-center gap-4">
            <flux:input wire:model.live.debounce.400ms="search" placeholder="Cari nomor surat atau wilayah..." icon="magnifying-glass" class="max-w-sm" />

            <flux:select wire:model.live="status" placeholder="Semua Status" class="max-w-xs">
                <flux:select.option value="">Semua Status</flux:select.option>
                @foreach ($statuses as $s)
                    <flux:select.option value="{{ $s->value }}">{{ $s->label() }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:select wire:model.live="jenis" placeholder="Semua Jenis" class="max-w-xs">
                <flux:select.option value="">Semua Jenis</flux:select.option>
                @foreach ($jenisOptions as $j)
                    <flux:select.option value="{{ $j->value }}">{{ $j->label() }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>

        <flux:text class="-mt-2 text-sm text-zinc-500">
            SPK berstatus "Selesai" diarsipkan &mdash; pilih status "Selesai" di atas untuk melihatnya.
        </flux:text>

        @if ($spk->isEmpty())
            <flux:card class="flex-1">
                <flux:text class="py-8 text-center text-zinc-500">
                    Belum ada surat. Klik "Buat Surat" untuk membuat SPK baru.
                </flux:text>
            </flux:card>
        @else
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($spk as $item)
                    <div wire:key="spk-{{ $item->id }}" class="flex flex-col overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-xs transition hover:shadow-md">
                        <div class="aspect-video w-full overflow-hidden bg-zinc-100">
                            @if ($item->cover_photo)
                                <img src="{{ Storage::url($item->cover_photo) }}" class="size-full object-cover" />
                            @else
                                <x-photo-placeholder class="size-full" />
                            @endif
                        </div>

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
                                <flux:badge size="sm" :color="match ($item->status) {
                                    StatusSpk::Aktif => 'blue',
                                    StatusSpk::Selesai => 'green',
                                    StatusSpk::Dibatalkan => 'zinc',
                                }">{{ $item->status->label() }}</flux:badge>
                            </div>

                            <flux:text class="text-sm text-zinc-500">
                                Deadline {{ $item->deadline->translatedFormat('d M Y') }}, {{ $item->rambu_pasang_count }} unit rambu
                            </flux:text>

                            <div class="mt-auto flex gap-2 pt-2">
                                <flux:button size="sm" variant="primary" class="flex-1" :href="route('admin.spk.show', $item)" wire:navigate>
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
