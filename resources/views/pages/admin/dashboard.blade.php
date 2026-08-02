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

            <a href="{{ route('admin.spk.index') }}" wire:navigate class="block">
                <flux:card class="flex items-center gap-4 border-l-4 border-l-cyan-500 transition hover:shadow-md">
                    <div class="flex size-12 shrink-0 items-center justify-center rounded-xl bg-cyan-100 text-cyan-700">
                        <flux:icon icon="document-text" class="size-6" />
                    </div>
                    <div class="min-w-0">
                        <flux:text class="text-sm text-zinc-500">SPK Aktif</flux:text>
                        <flux:heading size="xl">{{ $spkAktifCount }}</flux:heading>
                    </div>
                </flux:card>
            </a>

            <a href="{{ route('admin.validasi.index') }}" wire:navigate class="block">
                <flux:card class="flex items-center gap-4 border-l-4 border-l-violet-500 transition hover:shadow-md">
                    <div class="flex size-12 shrink-0 items-center justify-center rounded-xl bg-violet-100 text-violet-600">
                        <flux:icon icon="clipboard-document-check" class="size-6" />
                    </div>
                    <div class="min-w-0">
                        <flux:text class="text-sm text-zinc-500">Menunggu Validasi</flux:text>
                        <flux:heading size="xl">{{ $menungguValidasiCount }}</flux:heading>
                    </div>
                </flux:card>
            </a>
        </div>

        <flux:card class="flex flex-col gap-3">
            <div class="flex items-end justify-between gap-3">
                <div>
                    <flux:heading size="lg">Peta Rambu Perlu Perhatian</flux:heading>
                    <flux:subheading>Default: hanya rambu belum terpasang, rusak, atau menunggu validasi. Filter diterapkan otomatis begitu dipilih.</flux:subheading>
                </div>
                <flux:button type="button" id="dashboard-peta-unduh-pdf" variant="ghost" icon="arrow-down-tray" onclick="unduhPetaGambarPdf(getPetaFilterQueryDashboard(), 'dashboard-peta-unduh-pdf')">
                    Unduh PDF
                </flux:button>
            </div>

            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-5">
                <flux:select id="dashboard-peta-jenis" label="Jenis Rambu" placeholder="Semua Jenis" size="sm" onchange="terapkanFilterPetaDashboard()">
                    <flux:select.option value="">Semua Jenis</flux:select.option>
                    @foreach ($jenisRambuOptions as $j)
                        <flux:select.option value="{{ $j->id }}">{{ $j->nama_jenis }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select id="dashboard-peta-tingkat" label="Tingkat" placeholder="Default (perlu perhatian)" size="sm" onchange="terapkanFilterPetaDashboard()">
                    <flux:select.option value="">Default (perlu perhatian)</flux:select.option>
                    @foreach ($tingkatOptions as $value => $label)
                        <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:input id="dashboard-peta-tanggal-dari" type="date" label="Tugas Dari Tanggal" size="sm" onchange="terapkanFilterPetaDashboard()" />
                <flux:input id="dashboard-peta-tanggal-sampai" type="date" label="Tugas Sampai Tanggal" size="sm" onchange="terapkanFilterPetaDashboard()" />

                <div class="flex items-end">
                    <flux:button type="button" size="sm" variant="primary" class="w-full" onclick="resetFilterPetaDashboard()">Clear All</flux:button>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-4 text-sm">
                <div class="flex items-center gap-2"><span class="inline-block size-3 rounded-full" style="background:#ba1a1a"></span> Urgent / Prioritas / Tinggi</div>
                <div class="flex items-center gap-2"><span class="inline-block size-3 rounded-full" style="background:#eab308"></span> Rusak / Perbaikan Berjalan</div>
                <div class="flex items-center gap-2"><span class="inline-block size-3 rounded-full" style="background:#22d3ee"></span> Menunggu Validasi</div>
                <div class="flex items-center gap-2"><span class="inline-block size-3 rounded-full" style="background:#004655"></span> Selesai / Kondisi Baik</div>
                <div class="flex items-center gap-2"><span class="inline-block size-3 rounded-full" style="background:#9ca3af"></span> Belum Dikerjakan</div>
            </div>

            <div class="relative h-96 overflow-hidden rounded-xl border border-zinc-200">
                <div id="dashboard-peta" wire:ignore class="size-full"></div>
            </div>
        </flux:card>

        @script
        <script>
            function petaFilterQueryDashboard() {
                const params = new URLSearchParams();
                const jenis = document.getElementById('dashboard-peta-jenis').value;
                const tingkat = document.getElementById('dashboard-peta-tingkat').value;
                const dari = document.getElementById('dashboard-peta-tanggal-dari').value;
                const sampai = document.getElementById('dashboard-peta-tanggal-sampai').value;

                if (jenis) params.set('jenis_rambu_id', jenis);
                if (tingkat) params.set('tingkat', tingkat);
                if (dari) params.set('tanggal_dari', dari);
                if (sampai) params.set('tanggal_sampai', sampai);

                return params.toString();
            }

            function getPetaFilterQueryDashboard() {
                const query = petaFilterQueryDashboard();

                return @js(route('peta.export')) + (query ? '?' + query : '');
            }

            function terapkanFilterPetaDashboard() {
                const query = petaFilterQueryDashboard();

                // Only fall back to hiding "tenang" pins when no specific tingkat
                // was chosen — if the admin explicitly asks for e.g. "Selesai /
                // Kondisi Baik", that tingkat filter already narrows the result to
                // exactly those pins, so hiding them again here would show nothing.
                const hideTenang = ! document.getElementById('dashboard-peta-tingkat').value;

                initPetaRambu(
                    'dashboard-peta',
                    @js(route('peta.data')) + (query ? '?' + query : ''),
                    null,
                    @js(route('rambu.show', ['rambu' => '__ID__'])),
                    null,
                    null,
                    hideTenang
                );
            }

            function resetFilterPetaDashboard() {
                document.getElementById('dashboard-peta-jenis').value = '';
                document.getElementById('dashboard-peta-tingkat').value = '';
                document.getElementById('dashboard-peta-tanggal-dari').value = '';
                document.getElementById('dashboard-peta-tanggal-sampai').value = '';
                terapkanFilterPetaDashboard();
            }

            terapkanFilterPetaDashboard();
        </script>
        @endscript

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
                <div>
                    <flux:heading size="lg">SPK Perlu Perhatian</flux:heading>
                    <flux:subheading>Prioritas/urgensi tinggi ditampilkan lebih dulu, sisanya diurutkan dari progres paling rendah.</flux:subheading>
                </div>

                <div class="flex flex-col gap-3">
                    @forelse ($spkPrioritas as $row)
                        @php $persen = $row['total'] > 0 ? round(($row['selesai'] / $row['total']) * 100) : 100; @endphp
                        <div>
                            <div class="mb-1 flex items-center justify-between text-sm">
                                <span class="flex items-center gap-2 font-medium text-zinc-700">
                                    {{ $row['spk']->nomor_surat }}
                                    @if ($row['butuhPerhatian'])
                                        <flux:badge size="sm" color="red">Prioritas</flux:badge>
                                    @endif
                                </span>
                                <span class="text-zinc-500">{{ $row['selesai'] }}/{{ $row['total'] }} selesai &middot; deadline {{ $row['spk']->deadline->translatedFormat('d M') }}</span>
                            </div>
                            <div class="h-2 w-full overflow-hidden rounded-full bg-zinc-100">
                                <div class="h-full rounded-full {{ $row['butuhPerhatian'] ? 'bg-red-500' : 'bg-[#004655]' }}" style="width: {{ $persen }}%"></div>
                            </div>
                        </div>
                    @empty
                        <flux:text class="text-zinc-500">Tidak ada SPK aktif saat ini.</flux:text>
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
