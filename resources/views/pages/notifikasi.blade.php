    <div class="flex w-full flex-1 flex-col gap-6">
        <div class="flex items-end justify-between">
            <div>
                <flux:heading size="xl">Notifikasi</flux:heading>
                <flux:subheading>Pemberitahuan terkait SPK, laporan, dan validasi.</flux:subheading>
            </div>

            <flux:button variant="ghost" wire:click="tandaiSemuaDibaca">Tandai Semua Dibaca</flux:button>
        </div>

        <flux:card class="flex flex-col divide-y divide-zinc-200 p-0">
            @forelse ($notifikasi as $item)
                <div wire:key="notif-{{ $item->id }}" class="flex items-start justify-between gap-4 p-4 {{ $item->dibaca ? '' : 'bg-cyan-50' }}">
                    <div class="min-w-0">
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
                        <flux:button size="sm" variant="ghost" wire:click="tandaiDibaca({{ $item->id }})">
                            Tandai Dibaca
                        </flux:button>
                    @endif
                </div>
            @empty
                <div class="p-6 text-center text-zinc-500">Belum ada notifikasi.</div>
            @endforelse
        </flux:card>

        <div>
            {{ $notifikasi->links() }}
        </div>
    </div>
