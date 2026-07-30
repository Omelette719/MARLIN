    @php
        use App\Enums\StatusRambuPasang;
        use Illuminate\Support\Facades\Storage;
    @endphp

    <div class="flex w-full flex-1 flex-col gap-6">
        <div>
            <flux:heading size="xl">Laporan Pengerjaan Lapangan</flux:heading>
            <flux:subheading>Kirim laporan setelah pemasangan/perbaikan rambu selesai dikerjakan. Hanya perwakilan tim yang bisa mengirim laporan.</flux:subheading>
        </div>

        @if ($item)
            @php
                $editingInPlace = $item->status === StatusRambuPasang::MenungguValidasi;
                $existingFotoSesudah = $editingInPlace ? $item->laporanPengerjaan()->latest()->first()?->foto_sesudah : null;
            @endphp
            <flux:card class="flex flex-col gap-4" x-data="{
                ambilLokasi() {
                    if (! navigator.geolocation) return
                    navigator.geolocation.getCurrentPosition((pos) => {
                        $wire.set('koordinat_gps', pos.coords.latitude.toFixed(6) + ',' + pos.coords.longitude.toFixed(6))
                    })
                }
            }">
                <div>
                    <flux:heading size="lg">{{ $item->spk->nomor_surat }}</flux:heading>
                    <flux:subheading>{{ $item->rambu->wilayah }}, {{ $item->rambu->lokasi }} ({{ $item->jenis_pekerjaan->label() }})</flux:subheading>
                    @if ($item->status === StatusRambuPasang::Tertunda)
                        <flux:text class="text-sm text-amber-600">Rambu ini sudah ada kendalanya. Mengirim laporan di sini akan mengganti kendala tersebut dengan laporan selesai.</flux:text>
                    @endif
                </div>

                <form wire:submit="submit" class="flex flex-col gap-4">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <x-photo-upload
                            label="Foto Sebelum (dari survei SPK)"
                            :existing-url="$item->foto_survei ? Storage::url($item->foto_survei) : null"
                            placeholder-label="Belum ada foto survei"
                        />

                        <x-photo-upload
                            model="foto_sesudah"
                            label="Foto Sesudah"
                            :file="$foto_sesudah"
                            :existing-url="$existingFotoSesudah ? Storage::url($existingFotoSesudah) : null"
                            :required="! $editingInPlace"
                        />
                    </div>

                    <div class="flex items-end gap-3">
                        <flux:input wire:model="koordinat_gps" label="Koordinat GPS" placeholder="Kosongkan untuk memakai koordinat awal" class="flex-1" />
                        <flux:button type="button" variant="ghost" x-on:click="ambilLokasi()">Ambil Lokasi Sekarang</flux:button>
                    </div>

                    <flux:textarea wire:model="catatan_lapangan" label="Catatan Lapangan" rows="3" />

                    <div class="flex flex-col gap-3">
                        <div class="flex items-center justify-between">
                            <flux:heading size="sm">Barang/Bahan Terpakai</flux:heading>
                            <flux:button type="button" size="sm" icon="plus" wire:click="addBarangBahan">Tambah</flux:button>
                        </div>

                        @foreach ($barangBahan as $index => $bb)
                            <div wire:key="bb-{{ $index }}" class="flex items-start gap-3">
                                <flux:input wire:model="barangBahan.{{ $index }}.nama" placeholder="Nama barang" class="flex-1" />
                                <flux:input wire:model="barangBahan.{{ $index }}.jumlah" type="number" min="1" placeholder="Jumlah" class="w-24" />
                                <flux:input wire:model="barangBahan.{{ $index }}.satuan" placeholder="Satuan" class="w-28" />
                                @if (count($barangBahan) > 1)
                                    <flux:button type="button" size="sm" variant="danger" icon="trash" wire:click="removeBarangBahan({{ $index }})" />
                                @endif
                            </div>
                        @endforeach
                    </div>

                    <div class="flex justify-end gap-3">
                        <flux:button type="button" variant="ghost" wire:click="back">Batal</flux:button>
                        <flux:button type="submit" variant="primary">{{ $editingInPlace ? 'Simpan Perubahan' : 'Kirim Laporan' }}</flux:button>
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
                                    <flux:button size="sm" variant="primary" wire:click="selectItem({{ $rp->id }})">Buat Laporan</flux:button>
                                </flux:table.cell>
                            </flux:table.row>
                        @empty
                            <flux:table.row>
                                <flux:table.cell colspan="4" class="text-center text-zinc-500">
                                    Tidak ada tugas yang bisa dilaporkan saat ini.
                                </flux:table.cell>
                            </flux:table.row>
                        @endforelse
                    </flux:table.rows>
                </flux:table>
            </flux:card>
        @endif
    </div>
