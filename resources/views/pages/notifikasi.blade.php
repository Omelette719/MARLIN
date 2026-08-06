    <div class="flex w-full flex-1 flex-col gap-6">
        <div class="flex items-end justify-between">
            <div>
                <flux:heading size="xl">Notifikasi</flux:heading>
                <flux:subheading>Pemberitahuan terkait SPK, laporan, dan validasi.</flux:subheading>
            </div>

            <flux:button variant="ghost" wire:click="tandaiSemuaDibaca">Tandai Semua Dibaca</flux:button>
        </div>

        <div class="flex flex-col gap-3">
            @forelse ($notifikasi as $item)
                {{-- The whole notification is the "open" action (tap-anywhere,
                like a phone's notification center) instead of a separate
                "Lihat" button — only notifications with somewhere to go get
                the pointer cursor/click handler at all. --}}
                <div
                    wire:key="notif-{{ $item->id }}"
                    @if ($item->url) wire:click="bukaNotifikasi({{ $item->id }})" role="button" tabindex="0" @endif
                    class="flex items-start gap-3 rounded-2xl border p-4 shadow-sm transition
                        {{ $item->dibaca ? 'border-zinc-200 bg-white' : 'border-cyan-200 bg-cyan-50' }}
                        {{ $item->url ? 'cursor-pointer hover:shadow-md' : '' }}"
                >
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2">
                            <flux:text class="font-semibold text-zinc-800">{{ $item->judul }}</flux:text>
                            @if (! $item->dibaca)
                                <flux:badge color="cyan" size="sm">Baru</flux:badge>
                            @endif
                        </div>
                        <flux:text class="text-sm text-zinc-600">{{ $item->pesan }}</flux:text>
                        <flux:text class="text-xs text-zinc-400">{{ $item->created_at->diffForHumans() }}</flux:text>
                    </div>

                    @if (! $item->dibaca)
                        <flux:button size="sm" variant="ghost" class="shrink-0" wire:click.stop="tandaiDibaca({{ $item->id }})">
                            Tandai Dibaca
                        </flux:button>
                    @endif
                </div>
            @empty
                <flux:card>
                    <flux:text class="block py-6 text-center text-zinc-500">Belum ada notifikasi.</flux:text>
                </flux:card>
            @endforelse
        </div>

        <div>
            {{ $notifikasi->links() }}
        </div>
    </div>
