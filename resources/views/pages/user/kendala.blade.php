    @php
        use App\Enums\StatusRambuPasang;
        use Illuminate\Support\Facades\Storage;
    @endphp

    <div class="flex w-full flex-1 flex-col gap-6">
        <div>
            <flux:heading size="xl">Laporan Kendala Lapangan</flux:heading>
            <flux:subheading>Ajukan kendala jika ada rambu yang tidak bisa dikerjakan sesuai rencana. Hanya perwakilan tim yang bisa mengajukan.</flux:subheading>
        </div>

        @if ($item)
            @php
                $editingInPlace = $item->status === StatusRambuPasang::Tertunda;
                $existingFoto = $editingInPlace ? $item->kendala()->latest()->first()?->foto : null;
            @endphp
            <flux:card class="flex flex-col gap-4">
                <div>
                    <flux:heading size="lg">{{ $item->spk->nomor_surat }}</flux:heading>
                    <flux:subheading>{{ $item->rambu->wilayah }}, {{ $item->rambu->lokasi }} ({{ $item->jenis_pekerjaan->label() }})</flux:subheading>
                    @if ($item->status === StatusRambuPasang::MenungguValidasi)
                        <flux:text class="text-sm text-amber-600">Rambu ini sudah ada laporan penyelesaiannya. Mengirim di sini akan mengganti laporan tersebut dengan kendala.</flux:text>
                    @endif
                </div>

                @if ($catatanPenolakanSebelumnya)
                    <flux:callout variant="danger" icon="exclamation-triangle" heading="Ditolak admin, perlu direvisi">
                        {{ $catatanPenolakanSebelumnya }}
                    </flux:callout>
                @endif

                <form wire:submit="submit" class="flex flex-col gap-4">
                    <flux:textarea wire:model="alasan" label="Catatan Kendala" placeholder="Jelaskan kendala yang dihadapi di lapangan" rows="4" required />

                    <x-photo-upload
                        model="foto"
                        label="Foto Bukti Kendala"
                        :file="$foto"
                        :existing-url="$existingFoto ? Storage::url($existingFoto) : null"
                        :required="! $editingInPlace"
                    />

                    <div class="flex justify-end gap-3">
                        <flux:button type="button" variant="ghost" wire:click="back">Batal</flux:button>
                        <flux:button type="submit" variant="primary">{{ $editingInPlace ? 'Simpan Perubahan' : 'Ajukan Kendala' }}</flux:button>
                    </div>
                </form>
            </flux:card>
        @else
            <flux:card class="flex-1">
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>Nomor Surat</flux:table.column>
                        <flux:table.column>Lokasi</flux:table.column>
                        <flux:table.column>Jenis Pekerjaan</flux:table.column>
                        <flux:table.column align="end">Aksi</flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @forelse ($items as $rp)
                            <flux:table.row>
                                <flux:table.cell variant="strong">{{ $rp->spk->nomor_surat }}</flux:table.cell>
                                <flux:table.cell>{{ $rp->rambu->wilayah }}, {{ $rp->rambu->lokasi }}</flux:table.cell>
                                <flux:table.cell>{{ $rp->jenis_pekerjaan->label() }}</flux:table.cell>
                                <flux:table.cell align="end">
                                    <flux:button size="sm" variant="primary" wire:click="selectItem({{ $rp->id }})">Ajukan Kendala</flux:button>
                                </flux:table.cell>
                            </flux:table.row>
                        @empty
                            <flux:table.row>
                                <flux:table.cell colspan="4" class="text-center text-zinc-500">
                                    Tidak ada tugas yang bisa diajukan kendala saat ini.
                                </flux:table.cell>
                            </flux:table.row>
                        @endforelse
                    </flux:table.rows>
                </flux:table>
            </flux:card>
        @endif
    </div>
