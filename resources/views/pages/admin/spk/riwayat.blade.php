    @php
        use App\Enums\JenisPekerjaan;
        use App\Enums\StatusSpk;
        use App\Enums\Urgensi;
        use Illuminate\Support\Facades\Storage;
    @endphp

    <div class="flex w-full flex-1 flex-col gap-6">
        <div class="flex items-end justify-between">
            <div>
                <flux:heading size="xl">Riwayat SPK</flux:heading>
                <flux:subheading>Arsip SPK yang sudah selesai atau dibatalkan &mdash; yang masih aktif ada di <flux:link :href="route('admin.spk.index')" wire:navigate>Daftar Surat</flux:link>.</flux:subheading>
            </div>

            <flux:button variant="ghost" icon="arrow-left" :href="route('admin.spk.index')" wire:navigate>
                Kembali
            </flux:button>
        </div>

        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-5">
            <flux:input wire:model.live.debounce.400ms="search" placeholder="Cari nomor surat atau wilayah..." icon="magnifying-glass" />

            <flux:select wire:model.live="status" placeholder="Semua Status">
                <flux:select.option value="">Semua Status</flux:select.option>
                @foreach ($statuses as $s)
                    <flux:select.option value="{{ $s->value }}">{{ $s->label() }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:select wire:model.live="jenis" placeholder="Semua Jenis">
                <flux:select.option value="">Semua Jenis</flux:select.option>
                @foreach ($jenisOptions as $j)
                    <flux:select.option value="{{ $j->value }}">{{ $j->label() }}</flux:select.option>
                @endforeach
                <flux:select.option value="campuran">Pemasangan & Perbaikan</flux:select.option>
            </flux:select>

            <flux:input wire:model.live="tanggal_dari" type="date" label="Dari Tanggal" description="Berdasarkan tanggal selesai/dibatalkan" />
            <flux:input wire:model.live="tanggal_sampai" type="date" label="Sampai Tanggal" description="Berdasarkan tanggal selesai/dibatalkan" />
        </div>

        @if ($spk->isEmpty())
            <flux:card class="flex-1">
                <flux:text class="py-8 text-center text-zinc-500">
                    Belum ada SPK yang selesai atau dibatalkan.
                </flux:text>
            </flux:card>
        @else
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($spk as $item)
                    <div wire:key="spk-{{ $item->id }}" class="flex flex-col overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-xs transition hover:shadow-md {{ $item->dikerjakan_oleh_count > 0 ? 'ring-2 ring-blue-400' : '' }}">
                        <x-photo-slideshow :photos="$item->cover_photos->map(fn ($p) => Storage::url($p))" class="aspect-video w-full" />

                        <div class="flex flex-1 flex-col gap-2 p-4">
                            <div class="flex items-start justify-between gap-2">
                                <flux:heading size="sm">{{ $item->nomor_surat }}</flux:heading>
                                @php $jenisRingkasan = $item->jenisRingkasan(); @endphp
                                <flux:badge size="sm" :color="match (true) {
                                    $jenisRingkasan === null => 'violet',
                                    $jenisRingkasan === JenisPekerjaan::Perbaikan => 'amber',
                                    default => 'cyan',
                                }">
                                    {{ $jenisRingkasan?->label() ?? 'Pemasangan & Perbaikan' }}
                                </flux:badge>
                            </div>

                            <flux:text class="text-sm text-zinc-500">{{ $item->wilayah }}</flux:text>

                            <div class="flex flex-wrap items-center gap-2">
                                <flux:badge size="sm" :color="match ($item->status) {
                                    StatusSpk::Selesai => 'green',
                                    StatusSpk::Dibatalkan => 'zinc',
                                    default => 'zinc',
                                }">{{ $item->status->label() }}</flux:badge>
                                <flux:badge size="sm" :color="match ($item->urgensiSaatIni()) {
                                    Urgensi::Tinggi => 'red',
                                    Urgensi::Sedang => 'amber',
                                    Urgensi::Rendah => 'zinc',
                                }">{{ $item->urgensiSaatIni()->label() }}</flux:badge>
                                <flux:badge size="sm" :color="$item->dikerjakan_oleh_count > 0 ? 'blue' : 'zinc'" :variant="$item->dikerjakan_oleh_count > 0 ? 'solid' : null">
                                    {{ $item->dikerjakan_oleh_count > 0 ? 'Tim Terdaftar' : 'Belum Ada Tim' }}
                                </flux:badge>
                            </div>

                            <flux:text class="text-sm text-zinc-500">
                                Deadline {{ $item->deadline->translatedFormat('d M Y') }}, {{ $item->rambu_pasang_count }} unit rambu
                            </flux:text>

                            @if ($item->status === StatusSpk::Selesai && $item->durasiPengerjaanHari() !== null)
                                <div class="flex flex-wrap items-center gap-2">
                                    <flux:text class="text-sm text-zinc-500">Durasi {{ $item->durasiPengerjaanHari() }} hari</flux:text>
                                    <flux:badge size="sm" :color="$item->selisihDeadlineHari() > 0 ? 'red' : 'green'">
                                        {{ $item->selisihDeadlineLabel() }}
                                    </flux:badge>
                                </div>
                            @endif

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
