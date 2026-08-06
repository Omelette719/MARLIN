    @php
        use App\Enums\KondisiRambu;
        use Illuminate\Support\Facades\Storage;
    @endphp

    <div class="flex w-full flex-1 flex-col gap-6">
        <div>
            <flux:heading size="xl">Temuan Lapangan</flux:heading>
            <flux:subheading>Temuan kondisi rambu dari petugas yang belum ditindaklanjuti.</flux:subheading>
        </div>

        @if ($temuan->isEmpty())
            <flux:card class="flex-1">
                <flux:text class="py-8 text-center text-zinc-500">Tidak ada temuan yang belum ditindaklanjuti.</flux:text>
            </flux:card>
        @else
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($temuan as $item)
                    <div wire:key="temuan-{{ $item->id }}" class="flex flex-col overflow-hidden rounded-xl border border-zinc-200">
                        <div class="aspect-video w-full overflow-hidden bg-zinc-100">
                            @if ($item->foto)
                                <img src="{{ Storage::url($item->foto) }}" class="size-full object-cover" />
                            @else
                                <x-photo-placeholder class="size-full" />
                            @endif
                        </div>

                        <div class="flex flex-col gap-2 p-4">
                            <div class="flex items-start justify-between gap-2">
                                <div class="min-w-0">
                                    <flux:heading size="sm">{{ $item->rambu->jenisRambu?->nama_jenis }}</flux:heading>
                                    <flux:text class="truncate text-sm text-zinc-500">{{ $item->rambu->wilayah }}, {{ $item->rambu->lokasi }}</flux:text>
                                </div>
                                <flux:badge size="sm" :color="$item->kondisi_dilaporkan === KondisiRambu::Rusak ? 'red' : 'green'">
                                    {{ $item->kondisi_dilaporkan->label() }}
                                </flux:badge>
                            </div>

                            <flux:text class="text-sm text-zinc-600">
                                Dilaporkan oleh <strong>{{ $item->pelapor->name }}</strong>, {{ $item->created_at->translatedFormat('d M Y') }}
                            </flux:text>

                            @if ($item->catatan)
                                <flux:text class="text-sm text-zinc-600">{{ $item->catatan }}</flux:text>
                            @endif

                            <div class="mt-auto flex gap-2 pt-2">
                                <flux:button size="sm" variant="primary" class="flex-1" :href="route('admin.spk.create', ['laporan_kondisi' => $item->id])" wire:navigate>
                                    Buat SPK
                                </flux:button>

                                <flux:modal.trigger name="tolak-temuan-{{ $item->id }}">
                                    <flux:button size="sm" variant="ghost">
                                        Tolak
                                    </flux:button>
                                </flux:modal.trigger>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Modals live outside the grid on purpose: Flux's <ui-modal>
            wrapper still occupies a grid track even while closed, and
            sitting inside a `grid-cols-*` container as a sibling of the
            cards was eating one card-sized column per temuan, spreading
            the real cards apart instead of sitting next to each other. --}}
            @foreach ($temuan as $item)
                <x-confirm-modal
                    wire:key="tolak-temuan-modal-{{ $item->id }}"
                    name="tolak-temuan-{{ $item->id }}"
                    heading="Tolak temuan ini?"
                    text="Temuan akan ditandai sebagai ditolak dan tidak akan muncul lagi di daftar ini."
                    action="tolak({{ $item->id }})"
                    confirm-label="Ya, Tolak"
                    tone="danger"
                />
            @endforeach
        @endif
    </div>
