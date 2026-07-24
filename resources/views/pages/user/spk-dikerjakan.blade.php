    @php
        use App\Enums\JenisPekerjaan;
        use App\Enums\StatusRambuPasang;
        use Illuminate\Support\Facades\Storage;
    @endphp

    <div class="flex w-full flex-1 flex-col gap-6">
        <div>
            <flux:heading size="xl">SPK Sedang Dikerjakan</flux:heading>
            <flux:subheading>Surat perintah kerja yang timmu sudah terdaftar untuk mengerjakannya.</flux:subheading>
        </div>

        @if ($spk->isEmpty())
            <flux:card class="flex-1">
                <flux:text class="py-8 text-center text-zinc-500">
                    Belum ada surat yang sedang kamu kerjakan. Cari surat di "Daftar Surat Aktif" untuk mendaftarkan timmu.
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
                                <flux:badge size="sm" :color="match ($item->progress_status) {
                                    StatusRambuPasang::Selesai => 'green',
                                    StatusRambuPasang::MenungguValidasi => 'blue',
                                    StatusRambuPasang::Urgent, StatusRambuPasang::Revisi => 'red',
                                    StatusRambuPasang::Tertunda => 'amber',
                                    default => 'zinc',
                                }">{{ $item->progress_status->label() }}</flux:badge>

                                @if ($sayaPerwakilanIds->contains($item->id))
                                    <flux:badge color="blue" size="sm">Kamu Perwakilan</flux:badge>
                                @endif

                                @if ($item->siap_diajukan)
                                    <flux:badge color="cyan" size="sm">Siap Diajukan Laporan Akhir</flux:badge>
                                @endif
                            </div>

                            <flux:text class="text-sm text-zinc-500">
                                Deadline {{ $item->deadline->translatedFormat('d M Y') }}, {{ $item->rambu_pasang_count }} unit rambu
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
