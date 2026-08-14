    @php
        use App\Enums\JenisPekerjaan;
        use App\Enums\StatusSpk;
        use Illuminate\Support\Facades\Storage;
    @endphp

    <div class="flex w-full flex-1 flex-col gap-6">
        <div class="flex items-end justify-between">
            <div>
                <flux:heading size="xl">Riwayat Pekerjaan Saya</flux:heading>
                <flux:subheading>Surat yang pernah kamu kerjakan pada {{ $periodeLabel }}, termasuk yang sudah selesai. Bisa jadi bukti kerja kalau ditanya atasan.</flux:subheading>
            </div>

            <flux:input type="month" wire:model.live="bulan" label="Periode" />
        </div>

        @if ($spk->isEmpty())
            <flux:card class="flex-1">
                <flux:text class="py-8 text-center text-zinc-500">
                    Tidak ada surat yang kamu kerjakan pada periode ini.
                </flux:text>
            </flux:card>
        @else
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($spk as $item)
                    <div wire:key="spk-{{ $item->id }}" class="flex flex-col overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-xs transition hover:shadow-md">
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
                                    StatusSpk::Aktif => 'blue',
                                    StatusSpk::Dibatalkan => 'zinc',
                                }">{{ $item->status->label() }}</flux:badge>
                            </div>

                            <flux:text class="text-sm text-zinc-500">
                                Deadline {{ $item->deadline->translatedFormat('d M Y') }}, {{ $item->selesai_count }}/{{ $item->rambu_pasang_count }} rambu selesai
                            </flux:text>

                            <div class="mt-auto pt-2">
                                <flux:button size="sm" variant="primary" class="w-full" :href="route('user.spk.show', $item)" wire:navigate>
                                    Lihat Detail
                                </flux:button>
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
