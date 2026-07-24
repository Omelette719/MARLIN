    @php
        use App\Enums\JenisPekerjaan;
        use App\Enums\StatusSpk;
        use Illuminate\Support\Facades\Auth;
    @endphp

    <div class="flex w-full flex-1 flex-col gap-6">
        <div class="flex flex-col gap-1">
            <flux:heading size="xl">Halo, {{ Auth::user()->nama_panggilan ?: Auth::user()->name }}</flux:heading>
            <flux:subheading>Ringkasan pemasangan dan perbaikan rambu lalu lintas di Kota Banjarmasin, {{ now()->translatedFormat('d F Y') }}.</flux:subheading>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <flux:card class="flex items-center gap-4 border-l-4 border-l-green-500">
                <div class="flex size-12 shrink-0 items-center justify-center rounded-xl bg-green-100 text-green-600">
                    <flux:icon icon="check-circle" class="size-6" />
                </div>
                <div class="min-w-0">
                    <flux:text class="text-sm text-zinc-500">Rambu Terpasang</flux:text>
                    <flux:heading size="xl">{{ $rambuTerpasang }}</flux:heading>
                </div>
            </flux:card>

            <flux:card class="flex items-center gap-4 border-l-4 border-l-amber-500">
                <div class="flex size-12 shrink-0 items-center justify-center rounded-xl bg-amber-100 text-amber-600">
                    <flux:icon icon="clock" class="size-6" />
                </div>
                <div class="min-w-0">
                    <flux:text class="text-sm text-zinc-500">Belum Terpasang</flux:text>
                    <flux:heading size="xl">{{ $rambuBelumTerpasang }}</flux:heading>
                </div>
            </flux:card>

            <flux:card class="flex items-center gap-4 border-l-4 border-l-cyan-500">
                <div class="flex size-12 shrink-0 items-center justify-center rounded-xl bg-cyan-100 text-cyan-700">
                    <flux:icon icon="document-text" class="size-6" />
                </div>
                <div class="min-w-0">
                    <flux:text class="text-sm text-zinc-500">SPK Aktif</flux:text>
                    <flux:heading size="xl">{{ $spkAktifCount }}</flux:heading>
                </div>
            </flux:card>

            <flux:card class="flex items-center gap-4 border-l-4 border-l-violet-500">
                <div class="flex size-12 shrink-0 items-center justify-center rounded-xl bg-violet-100 text-violet-600">
                    <flux:icon icon="clipboard-document-check" class="size-6" />
                </div>
                <div class="min-w-0">
                    <flux:text class="text-sm text-zinc-500">Menunggu Validasi</flux:text>
                    <flux:heading size="xl">{{ $menungguValidasiCount }}</flux:heading>
                </div>
            </flux:card>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <flux:card class="flex flex-col gap-4 bg-[#004655] text-white lg:col-span-1">
                <div>
                    <flux:heading size="lg" class="text-white!">Progres Keseluruhan</flux:heading>
                    <flux:text class="text-cyan-100!">{{ $rambuTerpasang }} dari {{ $totalRambu }} rambu sudah terpasang</flux:text>
                </div>

                <div class="flex items-end gap-2">
                    <span class="text-4xl font-bold">{{ $progresPersen }}%</span>
                </div>

                <div class="h-2.5 w-full overflow-hidden rounded-full bg-white/20">
                    <div class="h-full rounded-full bg-[#86f0f3]" style="width: {{ $progresPersen }}%"></div>
                </div>

                @if ($temuanBaruCount > 0)
                    <div class="mt-2 flex items-center gap-2 rounded-lg bg-white/10 px-3 py-2 text-sm">
                        <flux:icon icon="exclamation-triangle" class="size-4 text-amber-300!" />
                        <span class="text-cyan-50!">{{ $temuanBaruCount }} temuan kondisi belum ditindaklanjuti</span>
                    </div>
                @endif

                <div class="mt-auto flex flex-wrap gap-2 pt-2">
                    <flux:button size="sm" variant="ghost" class="bg-white/10! text-white! hover:bg-white/20!" :href="route('admin.spk.create')" wire:navigate>
                        Buat Surat
                    </flux:button>
                    <flux:button size="sm" variant="ghost" class="bg-white/10! text-white! hover:bg-white/20!" :href="route('admin.validasi.index')" wire:navigate>
                        Validasi Laporan
                    </flux:button>
                    <flux:button size="sm" variant="ghost" class="bg-white/10! text-white! hover:bg-white/20!" :href="route('admin.temuan.index')" wire:navigate>
                        Temuan Lapangan
                    </flux:button>
                </div>
            </flux:card>

            <flux:card class="flex flex-col gap-4 lg:col-span-2">
                <flux:heading size="lg">Sebaran Rambu per Wilayah</flux:heading>

                <div class="flex flex-col gap-3">
                    @forelse ($wilayahBreakdown as $w)
                        @php $persen = $w->total > 0 ? round(($w->terpasang / $w->total) * 100) : 0; @endphp
                        <div>
                            <div class="mb-1 flex items-center justify-between text-sm">
                                <span class="font-medium text-zinc-700">{{ $w->wilayah }}</span>
                                <span class="text-zinc-500">{{ $w->terpasang }}/{{ $w->total }} terpasang</span>
                            </div>
                            <div class="h-2 w-full overflow-hidden rounded-full bg-zinc-100">
                                <div class="h-full rounded-full bg-[#004655]" style="width: {{ $persen }}%"></div>
                            </div>
                        </div>
                    @empty
                        <flux:text class="text-zinc-500">Belum ada data rambu.</flux:text>
                    @endforelse
                </div>
            </flux:card>
        </div>

        <flux:card class="flex-1">
            <div class="mb-4 flex items-end justify-between">
                <div>
                    <flux:heading size="lg">Surat Perintah Kerja Terbaru</flux:heading>
                    <flux:subheading>Daftar SPK yang paling baru dibuat, beserta progres rambu di dalamnya.</flux:subheading>
                </div>
                <flux:button size="sm" variant="ghost" :href="route('admin.spk.index')" wire:navigate>Lihat Semua</flux:button>
            </div>

            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Nomor Surat</flux:table.column>
                    <flux:table.column>Jenis</flux:table.column>
                    <flux:table.column>Wilayah</flux:table.column>
                    <flux:table.column align="center">Rambu</flux:table.column>
                    <flux:table.column>Status</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse ($spkTerbaru as $row)
                        <flux:table.row>
                            <flux:table.cell variant="strong">{{ $row['spk']->nomor_surat }}</flux:table.cell>
                            <flux:table.cell>
                                <flux:badge size="sm" :color="$row['spk']->jenis_spk === JenisPekerjaan::Perbaikan ? 'amber' : 'cyan'">
                                    {{ $row['spk']->jenis_spk->label() }}
                                </flux:badge>
                            </flux:table.cell>
                            <flux:table.cell>{{ $row['spk']->wilayah }}</flux:table.cell>
                            <flux:table.cell align="center">{{ $row['selesai'] }}/{{ $row['total'] }}</flux:table.cell>
                            <flux:table.cell>
                                <flux:badge size="sm" :color="match ($row['spk']->status) {
                                    StatusSpk::Selesai => 'green',
                                    StatusSpk::Aktif => 'blue',
                                    StatusSpk::Dibatalkan => 'zinc',
                                }">{{ $row['spk']->status->label() }}</flux:badge>
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="5" class="text-center text-zinc-500">
                                Belum ada SPK yang dibuat.
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </flux:card>
    </div>
