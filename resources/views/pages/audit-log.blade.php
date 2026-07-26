    <div class="flex w-full flex-1 flex-col gap-6">
        <div>
            <flux:heading size="xl">Riwayat Aktivitas</flux:heading>
            <flux:subheading>
                @if (Auth::user()->isAdmin())
                    Jejak seluruh aksi bisnis kunci dalam sistem.
                @else
                    Jejak aksi yang kamu lakukan sendiri.
                @endif
            </flux:subheading>
        </div>

        <flux:select wire:model.live="aksi" placeholder="Semua Aksi" class="max-w-xs">
            <flux:select.option value="">Semua Aksi</flux:select.option>
            @foreach ($aksiOptions as $opt)
                <flux:select.option value="{{ $opt }}">{{ $opt }}</flux:select.option>
            @endforeach
        </flux:select>

        <flux:card class="flex-1">
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Waktu</flux:table.column>
                    <flux:table.column>Aksi</flux:table.column>
                    <flux:table.column>Oleh</flux:table.column>
                    <flux:table.column>SPK</flux:table.column>
                    <flux:table.column>Keterangan</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse ($logs as $log)
                        <flux:table.row>
                            <flux:table.cell>{{ $log->created_at->format('d M Y H:i') }}</flux:table.cell>
                            <flux:table.cell>
                                <flux:badge size="sm">{{ $log->aksi }}</flux:badge>
                            </flux:table.cell>
                            <flux:table.cell>{{ $log->user->name }}</flux:table.cell>
                            <flux:table.cell>{{ $log->spk?->nomor_surat ?? 'Tidak terkait SPK' }}</flux:table.cell>
                            <flux:table.cell>{{ $log->keterangan ?? 'Tidak ada keterangan' }}</flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="5" class="text-center text-zinc-500">
                                Belum ada aktivitas yang tercatat.
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
