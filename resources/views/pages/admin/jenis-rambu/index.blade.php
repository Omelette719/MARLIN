    @php
        use Illuminate\Support\Facades\Storage;
    @endphp

    <div>
    <div class="flex w-full flex-1 flex-col gap-6">
        <div class="flex items-end justify-between">
            <div>
                <flux:heading size="xl">Jenis Rambu</flux:heading>
                <flux:subheading>Master data jenis rambu lalu lintas untuk dropdown pembuatan SPK.</flux:subheading>
            </div>

            @if ($isAdmin)
                <flux:button variant="primary" icon="plus" wire:click="tambahBaru">Tambah Jenis Rambu</flux:button>
            @endif
        </div>

        @if ($jenisRambu->isEmpty())
            <flux:card class="flex-1">
                <flux:text class="py-8 text-center text-zinc-500">Belum ada jenis rambu.</flux:text>
            </flux:card>
        @else
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
                @foreach ($jenisRambu as $jenis)
                    <div
                        wire:key="jenis-{{ $jenis->id }}"
                        wire:click="lihatRambu({{ $jenis->id }})"
                        class="group relative flex cursor-pointer flex-col overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-xs transition hover:shadow-md"
                    >
                        @if ($isAdmin)
                            <div class="absolute top-2 right-2 z-10 flex gap-1 opacity-0 transition group-hover:opacity-100">
                                <flux:button size="sm" variant="ghost" icon="pencil" class="bg-white/90! shadow" wire:click.stop="edit({{ $jenis->id }})" />
                                <flux:modal.trigger name="hapus-jenis-rambu-{{ $jenis->id }}" x-on:click.stop>
                                    <flux:button size="sm" variant="danger" icon="trash" />
                                </flux:modal.trigger>
                            </div>
                        @endif

                        <div class="aspect-square w-full bg-zinc-100">
                            @if ($jenis->gambar_referensi)
                                <img src="{{ Storage::url($jenis->gambar_referensi) }}" class="size-full object-contain p-6" />
                            @else
                                <x-photo-placeholder class="size-full" />
                            @endif
                        </div>

                        <div class="flex flex-col gap-1 p-4">
                            <div class="flex items-center justify-between gap-2">
                                <flux:heading size="sm">{{ $jenis->nama_jenis }}</flux:heading>
                                <flux:badge size="sm">{{ $jenis->bentuk_ikon->label() }}</flux:badge>
                            </div>
                            <flux:text class="text-sm text-zinc-500">{{ $jenis->rambu_count }} rambu terdaftar</flux:text>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Modals live outside the grid on purpose: Flux's <ui-modal>
            wrapper still occupies a grid track even while closed, and
            sitting inside a `grid-cols-*` container as a sibling of the
            cards was eating a card-sized slot per jenis rambu, spreading
            the real cards apart instead of sitting next to each other. --}}
            @if ($isAdmin)
                @foreach ($jenisRambu as $jenis)
                    <x-confirm-modal
                        wire:key="hapus-jenis-rambu-modal-{{ $jenis->id }}"
                        name="hapus-jenis-rambu-{{ $jenis->id }}"
                        heading="Hapus jenis rambu ini?"
                        text="{{ $jenis->nama_jenis }} akan dihapus permanen. Tindakan ini tidak bisa dibatalkan."
                        action="hapus({{ $jenis->id }})"
                        confirm-label="Ya, Hapus"
                        tone="danger"
                    />
                @endforeach
            @endif
        @endif
    </div>

    @if ($isAdmin)
    <flux:modal name="jenis-rambu-form" class="max-w-lg">
        <form wire:submit="save" class="flex flex-col gap-4">
            <flux:heading size="lg">{{ $editingId ? 'Edit Jenis Rambu' : 'Tambah Jenis Rambu' }}</flux:heading>

            <flux:input wire:model.live.debounce.500ms="nama_jenis" label="Nama Jenis" required />
            <flux:textarea wire:model="spesifikasi_standar" label="Spesifikasi Standar" rows="3" />

            <flux:radio.group wire:model="bentuk_ikon" label="Bentuk Ikon" variant="segmented">
                <flux:radio value="bulat" label="Bulat" />
                <flux:radio value="kotak" label="Kotak" />
            </flux:radio.group>

            <x-photo-upload
                model="gambar_referensi"
                label="Gambar Referensi / Ikon"
                :file="$gambar_referensi"
                :existing-url="$editingId && optional($jenisRambu->firstWhere('id', $editingId))->gambar_referensi ? Storage::url($jenisRambu->firstWhere('id', $editingId)->gambar_referensi) : null"
                aspect="square"
                description="Dipakai sebagai ikon titik di peta. Opsional."
            />

            <div class="flex justify-end gap-3">
                <flux:modal.close>
                    <flux:button type="button" variant="ghost">Batal</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary">Simpan</flux:button>
            </div>
        </form>
    </flux:modal>
    @endif
    </div>
