    @php
        use App\Enums\JenisPekerjaan;
        use App\Enums\StatusRambuPasang;
        use App\Enums\Urgensi;
        use Illuminate\Support\Facades\Storage;
    @endphp

    <div class="flex w-full flex-1 flex-col gap-6">
        <div class="flex items-end justify-between">
            <div>
                <flux:heading size="xl">Daftar Surat Aktif</flux:heading>
                <flux:subheading>Daftar surat pemasangan rambu yang menjadi tanggung jawab petugas lapangan.</flux:subheading>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <flux:card class="flex items-center gap-4">
                <div class="flex size-12 shrink-0 items-center justify-center rounded-full bg-blue-100 text-blue-600">
                    <flux:icon icon="clipboard-document-list" class="size-6" />
                </div>
                <div class="min-w-0">
                    <flux:text class="text-sm text-zinc-500">Aktif</flux:text>
                    <flux:heading size="xl">{{ $aktifCount }}</flux:heading>
                </div>
            </flux:card>

            <flux:card class="flex items-center gap-4">
                <div class="flex size-12 shrink-0 items-center justify-center rounded-full bg-cyan-100 text-cyan-600">
                    <flux:icon icon="arrow-path" class="size-6" />
                </div>
                <div class="min-w-0">
                    <flux:text class="text-sm text-zinc-500">Progres</flux:text>
                    <flux:heading size="xl">{{ $progresCount }}</flux:heading>
                </div>
            </flux:card>

            <flux:card class="flex items-center gap-4">
                <div class="flex size-12 shrink-0 items-center justify-center rounded-full bg-red-100 text-red-600">
                    <flux:icon icon="exclamation-triangle" class="size-6" />
                </div>
                <div class="min-w-0">
                    <flux:text class="text-sm text-zinc-500">Mendekati Deadline</flux:text>
                    <flux:heading size="xl">{{ $mendekatiDeadlineCount }}</flux:heading>
                </div>
            </flux:card>

            <flux:card class="flex items-center gap-4">
                <div class="flex size-12 shrink-0 items-center justify-center rounded-full bg-green-100 text-green-600">
                    <flux:icon icon="check-circle" class="size-6" />
                </div>
                <div class="min-w-0">
                    <flux:text class="text-sm text-zinc-500">Selesai Bulan Ini</flux:text>
                    <flux:heading size="xl">{{ $selesaiBulanIniCount }}</flux:heading>
                </div>
            </flux:card>
        </div>

        <flux:card class="flex flex-col gap-3">
            <div class="flex items-end justify-between gap-3">
                <div>
                    <flux:heading size="lg">Peta Rambu Perlu Perhatian</flux:heading>
                    <flux:subheading>Default: hanya rambu belum terpasang, rusak, atau menunggu validasi. Filter diterapkan otomatis begitu dipilih.</flux:subheading>
                </div>
                <flux:button type="button" id="user-dashboard-peta-unduh-pdf" variant="ghost" icon="arrow-down-tray" onclick="unduhPetaGambarPdf(getPetaFilterQueryUserDashboard(), 'user-dashboard-peta-unduh-pdf')">
                    Unduh PDF
                </flux:button>
            </div>

            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-5">
                <flux:select id="user-dashboard-peta-jenis" label="Jenis Rambu" placeholder="Semua Jenis" size="sm" onchange="terapkanFilterPetaUserDashboard()">
                    <flux:select.option value="">Semua Jenis</flux:select.option>
                    @foreach ($jenisRambuOptions as $j)
                        <flux:select.option value="{{ $j->id }}">{{ $j->nama_jenis }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select id="user-dashboard-peta-tingkat" label="Tingkat" placeholder="Default (perlu perhatian)" size="sm" onchange="terapkanFilterPetaUserDashboard()">
                    <flux:select.option value="">Default (perlu perhatian)</flux:select.option>
                    @foreach ($tingkatOptions as $value => $label)
                        {{-- Same reasoning as the admin widget: "Selesai / Kondisi
                        Baik" would just duplicate what hideTenang already hides
                        by default when no tingkat is chosen. --}}
                        @continue($value === 'selesai')
                        <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select id="user-dashboard-peta-kecamatan" label="Kecamatan" placeholder="Semua Kecamatan" size="sm" onchange="terapkanFilterPetaUserDashboard()">
                    <flux:select.option value="">Semua Kecamatan</flux:select.option>
                    @foreach ($kecamatanOptions as $k)
                        <flux:select.option value="{{ $k }}">{{ $k }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select id="user-dashboard-peta-kelurahan" label="Kelurahan" placeholder="Semua Kelurahan" size="sm" onchange="terapkanFilterPetaUserDashboard()">
                    <flux:select.option value="">Semua Kelurahan</flux:select.option>
                    @foreach ($kelurahanOptions as $k)
                        <flux:select.option value="{{ $k }}">{{ $k }}</flux:select.option>
                    @endforeach
                </flux:select>

                <div class="flex items-end">
                    <flux:button type="button" size="sm" variant="primary" class="w-full" onclick="resetFilterPetaUserDashboard()">Clear All</flux:button>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-4 text-sm">
                <div class="flex items-center gap-2"><span class="inline-block size-3 rounded-full" style="background:#ba1a1a"></span> Tinggi / Prioritas</div>
                <div class="flex items-center gap-2"><span class="inline-block size-3 rounded-full" style="background:#eab308"></span> Sedang</div>
                <div class="flex items-center gap-2"><span class="inline-block size-3 rounded-full" style="background:#22d3ee"></span> Menunggu Validasi</div>
                <div class="flex items-center gap-2"><span class="inline-block size-3 rounded-full" style="background:#9ca3af"></span> Rendah</div>
            </div>

            {{-- isolate: same fix as pages/peta.blade.php and the admin dashboard
            widget — contains Leaflet's internal z-index (up to 1000 for its
            controls) so it can't paint over the mobile sidebar drawer (z-20). --}}
            <div class="relative isolate h-96 overflow-hidden rounded-xl border border-zinc-200">
                <div id="user-dashboard-peta" wire:ignore class="size-full"></div>
            </div>
        </flux:card>

        @script
        <script>
            // Same isolated-scope caveat as the admin dashboard widget: this
            // block runs as its own AsyncFunction, so every handler the filter
            // controls/buttons call by name has to be attached to `window`
            // explicitly. Named distinctly from the admin widget's
            // *PetaDashboard functions so the two never collide if both ever
            // happen to be defined in the same session (e.g. an admin account
            // that's also flagged as petugas).
            window.petaFilterQueryUserDashboard = function () {
                const params = new URLSearchParams();
                const jenis = document.getElementById('user-dashboard-peta-jenis').value;
                const tingkat = document.getElementById('user-dashboard-peta-tingkat').value;
                const kecamatan = document.getElementById('user-dashboard-peta-kecamatan').value;
                const kelurahan = document.getElementById('user-dashboard-peta-kelurahan').value;

                if (jenis) params.set('jenis_rambu_id', jenis);
                if (tingkat) params.set('tingkat', tingkat);
                if (kecamatan) params.set('kecamatan', kecamatan);
                if (kelurahan) params.set('kelurahan', kelurahan);

                return params.toString();
            };

            window.getPetaFilterQueryUserDashboard = function () {
                const query = window.petaFilterQueryUserDashboard();

                return @js(route('peta.export')) + (query ? '?' + query : '');
            };

            window.terapkanFilterPetaUserDashboard = function () {
                const query = window.petaFilterQueryUserDashboard();

                const hideTenang = ! document.getElementById('user-dashboard-peta-tingkat').value;

                initPetaRambu(
                    'user-dashboard-peta',
                    @js(route('peta.data')) + (query ? '?' + query : ''),
                    null,
                    @js(route('rambu.show', ['rambu' => '__ID__'])),
                    null,
                    @js(route('user.temuan', ['rambu_id' => '__ID__'])),
                    hideTenang
                );
            };

            window.resetFilterPetaUserDashboard = function () {
                document.getElementById('user-dashboard-peta-jenis').value = '';
                document.getElementById('user-dashboard-peta-tingkat').value = '';
                document.getElementById('user-dashboard-peta-kecamatan').value = '';
                document.getElementById('user-dashboard-peta-kelurahan').value = '';
                window.terapkanFilterPetaUserDashboard();
            };

            window.terapkanFilterPetaUserDashboard();
        </script>
        @endscript

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <flux:card class="flex flex-col gap-4">
                <div>
                    <flux:heading size="lg">SPK Perlu Perhatian</flux:heading>
                    <flux:subheading>Dari SPK yang timmu ikuti. Prioritas/urgensi tinggi ditampilkan lebih dulu, sisanya diurutkan dari progres paling rendah.</flux:subheading>
                </div>

                <div class="flex flex-col gap-3">
                    @forelse ($spkPerluPerhatian as $row)
                        @php $persen = $row['total'] > 0 ? round(($row['selesai'] / $row['total']) * 100) : 100; @endphp
                        <div>
                            <div class="mb-1 flex items-center justify-between text-sm">
                                <span class="flex items-center gap-2 font-medium text-zinc-700">
                                    <flux:link :href="route('user.spk.show', $row['spk'])" wire:navigate>{{ $row['spk']->nomor_surat }}</flux:link>
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
                        <flux:text class="text-zinc-500">Belum ada SPK yang kamu ikuti saat ini.</flux:text>
                    @endforelse
                </div>
            </flux:card>

            <flux:card class="flex flex-col gap-4">
                <div>
                    <flux:heading size="lg">Saran SPK untuk Bergabung</flux:heading>
                    <flux:subheading>SPK yang belum ada tim, diutamakan yang berada di kecamatan sama atau paling dekat dengan pekerjaanmu yang sedang berjalan.</flux:subheading>
                </div>

                <div class="flex flex-col gap-3">
                    @forelse ($saranSpk as $row)
                        <div class="flex items-center justify-between gap-2 text-sm">
                            <div>
                                <div class="flex items-center gap-2 font-medium text-zinc-700">
                                    <flux:link :href="route('user.spk.show', $row['spk'])" wire:navigate>{{ $row['spk']->nomor_surat }}</flux:link>
                                    @if ($row['samaKecamatan'])
                                        <flux:badge size="sm" color="blue">Kecamatan Sama</flux:badge>
                                    @elseif ($row['jarakMeter'] !== null)
                                        <flux:badge size="sm" color="zinc">{{ number_format($row['jarakMeter'] / 1000, 1) }} km</flux:badge>
                                    @endif
                                </div>
                                <div class="text-zinc-500">{{ $row['spk']->wilayah }}</div>
                            </div>
                            <span class="shrink-0 text-zinc-500">deadline {{ $row['spk']->deadline->translatedFormat('d M') }}</span>
                        </div>
                    @empty
                        <flux:text class="text-zinc-500">Tidak ada saran SPK yang bisa ditampilkan saat ini.</flux:text>
                    @endforelse
                </div>
            </flux:card>
        </div>

        <flux:input wire:model.live.debounce.400ms="search" placeholder="Cari nomor surat atau wilayah..." icon="magnifying-glass" class="max-w-sm" />

        @if ($spk->isEmpty())
            <flux:card class="flex-1">
                <flux:text class="py-8 text-center text-zinc-500">Belum ada surat aktif saat ini.</flux:text>
            </flux:card>
        @else
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($spk as $item)
                    @php
                        $sayaGabung = $joinedSpkIds->contains($item->id);
                        $adaTim = $item->dikerjakan_oleh_count > 0;
                    @endphp
                    <div wire:key="spk-{{ $item->id }}" class="flex flex-col overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-xs transition hover:shadow-md {{ $sayaGabung ? 'ring-2 ring-blue-400' : ($adaTim ? 'ring-2 ring-amber-300' : '') }}">
                        <x-photo-slideshow :photos="$item->cover_photos->map(fn ($p) => Storage::url($p))" class="aspect-video w-full" />

                        <div class="flex flex-1 flex-col gap-2 p-4">
                            <div class="flex items-start justify-between gap-2">
                                <flux:heading size="sm">{{ $item->nomor_surat }}</flux:heading>
                                @php $jenisRingkasan = $item->jenisRingkasan(); @endphp
                                <flux:badge size="sm" :color="match (true) {
                                    $jenisRingkasan === null => 'violet',
                                    $jenisRingkasan === JenisPekerjaan::Perbaikan => 'amber',
                                    default => 'cyan',
                                }">
                                    {{ $jenisRingkasan?->label() ?? 'Pemasangan & Perbaikan' }}
                                </flux:badge>
                            </div>

                            <flux:text class="text-sm text-zinc-500">{{ $item->wilayah }}</flux:text>

                            <div class="flex flex-wrap items-center gap-2">
                                @if ($item->prioritas)
                                    <flux:badge color="red" size="sm">Prioritas</flux:badge>
                                @endif
                                <flux:badge size="sm" :color="match ($item->urgensiSaatIni()) {
                                    Urgensi::Tinggi => 'red',
                                    Urgensi::Sedang => 'amber',
                                    Urgensi::Rendah => 'zinc',
                                }">{{ $item->urgensiSaatIni()->label() }}</flux:badge>
                                <flux:badge size="sm" :color="match ($item->progress_status) {
                                    StatusRambuPasang::Selesai => 'green',
                                    StatusRambuPasang::MenungguValidasi => 'blue',
                                    StatusRambuPasang::Urgent, StatusRambuPasang::Revisi => 'red',
                                    StatusRambuPasang::Tertunda => 'amber',
                                    default => 'zinc',
                                }">{{ $item->progress_status->label() }}</flux:badge>
                                @if ($sayaGabung && $item->siap_diajukan)
                                    <flux:badge color="cyan" size="sm">Siap Diajukan Laporan Akhir</flux:badge>
                                @endif
                                @if ($sayaGabung)
                                    <flux:badge color="blue" variant="solid" size="sm">Sudah Bergabung</flux:badge>
                                @elseif ($adaTim)
                                    <flux:badge color="amber" size="sm">Sudah Ada Tim Lain</flux:badge>
                                @else
                                    <flux:badge color="zinc" size="sm">Belum Ada Tim</flux:badge>
                                @endif
                            </div>

                            <flux:text class="text-sm text-zinc-500">
                                Deadline {{ $item->deadline->translatedFormat('d M Y') }}, {{ $item->rambu_pasang_count }} unit rambu
                            </flux:text>

                            <div class="mt-auto flex gap-2 pt-2">
                                <flux:button size="sm" variant="primary" class="flex-1" :href="route('user.spk.show', $item)" wire:navigate>
                                    Lihat Detail
                                </flux:button>
                                @if ($sayaGabung)
                                    <flux:button size="sm" variant="ghost" icon="arrow-down-tray" :href="route('spk.surat-pengantar', $item)" target="_blank" />
                                @else
                                    <flux:button size="sm" variant="ghost" icon="arrow-down-tray" wire:click="tautanSuratPengantarDitolak" />
                                @endif
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
