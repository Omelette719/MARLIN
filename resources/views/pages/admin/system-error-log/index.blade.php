    @php
        use App\Enums\ErrorLevel;
    @endphp

    <div class="flex w-full flex-1 flex-col gap-6">
        <div>
            <flux:heading size="xl">Log Error Sistem</flux:heading>
            <flux:subheading>Jejak error teknis (exception, kegagalan query, dsb), terpisah dari riwayat aksi bisnis.</flux:subheading>
        </div>

        <flux:select wire:model.live="level" placeholder="Semua Level" class="max-w-xs">
            <flux:select.option value="">Semua Level</flux:select.option>
            @foreach ($levelOptions as $opt)
                <flux:select.option value="{{ $opt->value }}">{{ $opt->label() }}</flux:select.option>
            @endforeach
        </flux:select>

        <flux:card class="flex-1">
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Waktu</flux:table.column>
                    <flux:table.column>Level</flux:table.column>
                    <flux:table.column>Pesan</flux:table.column>
                    <flux:table.column>Endpoint</flux:table.column>
                    <flux:table.column>User</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse ($logs as $log)
                        <flux:table.row>
                            <flux:table.cell>{{ $log->created_at->format('d M Y H:i') }}</flux:table.cell>
                            <flux:table.cell>
                                <flux:badge size="sm" :color="match ($log->level) {
                                    ErrorLevel::Critical => 'red',
                                    ErrorLevel::Error => 'amber',
                                    ErrorLevel::Warning => 'yellow',
                                    ErrorLevel::Info => 'zinc',
                                }">{{ $log->level->label() }}</flux:badge>
                            </flux:table.cell>
                            <flux:table.cell>{{ $log->pesan }}</flux:table.cell>
                            <flux:table.cell class="max-w-xs truncate">{{ $log->endpoint ?: 'Tidak diketahui' }}</flux:table.cell>
                            <flux:table.cell>{{ $log->user?->name ?? 'Sistem' }}</flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="5" class="text-center text-zinc-500">
                                Belum ada error yang tercatat.
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>

            <div class="mt-4">
                {{ $logs->links() }}
            </div>
        </flux:card>
    </div>
