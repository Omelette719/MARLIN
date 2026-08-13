    <div class="flex w-full flex-1 flex-col gap-3">
        <div class="flex flex-wrap items-center gap-4 text-sm">
            <div class="flex items-center gap-2"><span class="inline-block size-3 rounded-full" style="background:#ba1a1a"></span> Urgent / Prioritas / Tinggi</div>
            <div class="flex items-center gap-2"><span class="inline-block size-3 rounded-full" style="background:#eab308"></span> Rusak / Perbaikan Berjalan</div>
            <div class="flex items-center gap-2"><span class="inline-block size-3 rounded-full" style="background:#22d3ee"></span> Menunggu Validasi</div>
            <div class="flex items-center gap-2"><span class="inline-block size-3 rounded-full" style="background:#004655"></span> Selesai / Kondisi Baik</div>
            <div class="flex items-center gap-2"><span class="inline-block size-3 rounded-full" style="background:#9ca3af"></span> Belum Dikerjakan</div>
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
        initPetaRambu(
            'peta-rambu',
            @js(route('peta.data')),
            'peta-koordinat',
            @js(route('rambu.show', ['rambu' => '__ID__'])),
            @js($focus),
            @js($isAdmin ? null : route('user.temuan', ['rambu_id' => '__ID__']))
        );
    </script>
    @endscript
