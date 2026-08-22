    <div class="flex w-full flex-1 flex-col gap-3">
        <div class="grid grid-cols-1 gap-2 text-sm sm:grid-cols-2 lg:grid-cols-4">
            <div class="flex items-start gap-2">
                <span class="mt-1 inline-block size-3 shrink-0 rounded-full" style="background:#ba1a1a"></span>
                <div>
                    <div class="font-medium text-zinc-700">Tinggi / Prioritas</div>
                    <ul class="list-inside list-disc text-xs text-zinc-500">
                        <li>Tinggi: sisa waktu pengerjaan kurang dari 4 hari.</li>
                        <li>Prioritas: SPK-nya ditandai sebagai prioritas oleh admin.</li>
                    </ul>
                </div>
            </div>
            <div class="flex items-start gap-2">
                <span class="mt-1 inline-block size-3 shrink-0 rounded-full" style="background:#eab308"></span>
                <div>
                    <div class="font-medium text-zinc-700">Sedang</div>
                    <div class="text-xs text-zinc-500">Sisa waktu pengerjaan kurang dari 7 hari.</div>
                </div>
            </div>
            <div class="flex items-start gap-2">
                <span class="mt-1 inline-block size-3 shrink-0 rounded-full" style="background:#22d3ee"></span>
                <div>
                    <div class="font-medium text-zinc-700">Menunggu Validasi</div>
                    <div class="text-xs text-zinc-500">Petugas sudah mengirim laporan pengerjaan, menunggu admin memeriksa dan menyetujuinya.</div>
                </div>
            </div>
            <div class="flex items-start gap-2">
                <span class="mt-1 inline-block size-3 shrink-0 rounded-full" style="background:#9ca3af"></span>
                <div>
                    <div class="font-medium text-zinc-700">Rendah</div>
                    <div class="text-xs text-zinc-500">Sisa waktu pengerjaan masih lama atau belum ada laporan pengerjaan masuk untuk rambu ini.</div>
                </div>
            </div>
        </div>

        <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
            <div class="grid flex-1 grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <flux:select id="peta-filter-jenis" label="Jenis Rambu" placeholder="Semua Jenis" size="sm" onchange="terapkanFilterPetaUtama()">
                    <flux:select.option value="">Semua Jenis</flux:select.option>
                    @foreach ($jenisRambuOptions as $j)
                        <flux:select.option value="{{ $j->id }}">{{ $j->nama_jenis }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select id="peta-filter-tingkat" label="Tingkat" placeholder="Semua Tingkat" size="sm" onchange="terapkanFilterPetaUtama()">
                    <flux:select.option value="">Semua Tingkat</flux:select.option>
                    @foreach ($tingkatOptions as $value => $label)
                        <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select id="peta-filter-kecamatan" label="Kecamatan" placeholder="Semua Kecamatan" size="sm" onchange="terapkanFilterPetaUtama()">
                    <flux:select.option value="">Semua Kecamatan</flux:select.option>
                    @foreach ($kecamatanOptions as $k)
                        <flux:select.option value="{{ $k }}">{{ $k }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select id="peta-filter-kelurahan" label="Kelurahan" placeholder="Semua Kelurahan" size="sm" onchange="terapkanFilterPetaUtama()">
                    <flux:select.option value="">Semua Kelurahan</flux:select.option>
                    @foreach ($kelurahanOptions as $k)
                        <flux:select.option value="{{ $k }}">{{ $k }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>

            <div class="flex gap-2">
                <flux:button type="button" size="sm" onclick="resetFilterPetaUtama()">Clear All</flux:button>
                <flux:button type="button" id="peta-unduh-pdf" size="sm" variant="primary" icon="arrow-down-tray" onclick="unduhPetaGambarPdf(getPetaFilterQueryUtama(), 'peta-unduh-pdf')">
                    Unduh PDF
                </flux:button>
            </div>
        </div>

        {{-- isolate: Leaflet's own controls/panes go up to z-index:1000 internally
        (see leaflet.css), which without a contained stacking context here
        would outrank the mobile sidebar drawer (z-20) and paint on top of
        it whenever the sidebar is opened on a small screen. --}}
        <div class="relative isolate h-[calc(100dvh-155px)] overflow-hidden rounded-xl border border-zinc-200">
            <div id="peta-rambu" wire:ignore class="size-full"></div>
            <div
                id="peta-koordinat"
                class="pointer-events-none absolute top-3 right-3 z-1000 rounded-lg bg-white/95 px-3 py-1.5 font-mono text-xs text-zinc-700 shadow dark:bg-zinc-800/95 dark:text-zinc-200"
            >
                Gerakkan kursor di peta
            </div>
        </div>
    </div>

    @script
    <script>
        // Same isolated-scope caveat as the dashboard widgets: this block runs
        // as its own AsyncFunction, so every handler the filter controls/
        // buttons call by name has to be attached to `window` explicitly.
        // Named distinctly (*PetaUtama) so this never collides with the
        // dashboard widgets' own *PetaDashboard/*PetaUserDashboard functions.
        window.petaFilterQueryUtama = function () {
            const params = new URLSearchParams();
            const jenis = document.getElementById('peta-filter-jenis').value;
            const tingkat = document.getElementById('peta-filter-tingkat').value;
            const kecamatan = document.getElementById('peta-filter-kecamatan').value;
            const kelurahan = document.getElementById('peta-filter-kelurahan').value;

            if (jenis) params.set('jenis_rambu_id', jenis);
            if (tingkat) params.set('tingkat', tingkat);
            if (kecamatan) params.set('kecamatan', kecamatan);
            if (kelurahan) params.set('kelurahan', kelurahan);

            return params.toString();
        };

        window.getPetaFilterQueryUtama = function () {
            const query = window.petaFilterQueryUtama();

            return @js(route('peta.export')) + (query ? '?' + query : '');
        };

        // focus only matters on the very first render (zooming/opening a pin
        // someone navigated here to look at) — re-applying a filter afterward
        // never needs to re-focus anything.
        window.terapkanFilterPetaUtama = function (focus = null) {
            const query = window.petaFilterQueryUtama();

            initPetaRambu(
                'peta-rambu',
                @js(route('peta.data')) + (query ? '?' + query : ''),
                'peta-koordinat',
                @js(route('rambu.show', ['rambu' => '__ID__'])),
                focus,
                @js($isAdmin ? null : route('user.temuan', ['rambu_id' => '__ID__']))
            );
        };

        window.resetFilterPetaUtama = function () {
            document.getElementById('peta-filter-jenis').value = '';
            document.getElementById('peta-filter-tingkat').value = '';
            document.getElementById('peta-filter-kecamatan').value = '';
            document.getElementById('peta-filter-kelurahan').value = '';
            window.terapkanFilterPetaUtama();
        };

        window.terapkanFilterPetaUtama(@js($focus));
    </script>
    @endscript
