@php
    $homeRoute = auth()->user()?->isAdmin() ? 'admin.dashboard' : 'dashboard';
@endphp

<flux:sidebar sticky :collapsible="true" class="border-e border-zinc-200 bg-zinc-50">
    <flux:sidebar.header>
        <x-app-logo :sidebar="true" href="{{ route($homeRoute) }}" wire:navigate />
        <flux:sidebar.collapse />
    </flux:sidebar.header>

    <flux:sidebar.nav>
        @if (auth()->user()?->isAdmin())
            <flux:sidebar.group :heading="__('Admin')" expandable icon="squares-2x2" class="grid">
                <flux:sidebar.item icon="home" :href="route($homeRoute)" :current="request()->routeIs($homeRoute)" wire:navigate>
                    {{ __('Dashboard') }}
                </flux:sidebar.item>
                <flux:sidebar.item icon="document-text" :href="route('admin.spk.index')" :current="request()->routeIs(['admin.spk.index', 'admin.spk.show', 'admin.spk.edit'])" wire:navigate>
                    {{ __('Daftar Surat') }}
                </flux:sidebar.item>
                <flux:sidebar.item icon="document-plus" :href="route('admin.spk.create')" :current="request()->routeIs('admin.spk.create')" wire:navigate>
                    {{ __('Buat Surat') }}
                </flux:sidebar.item>
                <flux:sidebar.item icon="archive-box" :href="route('admin.spk.riwayat')" :current="request()->routeIs('admin.spk.riwayat')" wire:navigate>
                    {{ __('Riwayat SPK') }}
                </flux:sidebar.item>
                <flux:sidebar.item icon="clipboard-document-check" :href="route('admin.validasi.index')" :current="request()->routeIs('admin.validasi.*')" wire:navigate>
                    {{ __('Validasi Laporan') }}
                </flux:sidebar.item>
                <flux:sidebar.item icon="map-pin" :href="route('admin.temuan.index')" :current="request()->routeIs('admin.temuan.*')" wire:navigate>
                    {{ __('Temuan Lapangan') }}
                </flux:sidebar.item>
                <flux:sidebar.item icon="document-chart-bar" :href="route('admin.laporan.index')" :current="request()->routeIs('admin.laporan.index')" wire:navigate>
                    {{ __('Laporan Bulanan') }}
                </flux:sidebar.item>
                <flux:sidebar.item icon="document-chart-bar" :href="route('admin.laporan.rambu')" :current="request()->routeIs('admin.laporan.rambu')" wire:navigate>
                    {{ __('Laporan Rambu') }}
                </flux:sidebar.item>
                <flux:sidebar.item icon="map" :href="route('peta')" :current="request()->routeIs('peta')" wire:navigate>
                    {{ __('Peta Rambu') }}
                </flux:sidebar.item>
                <flux:sidebar.item icon="clipboard-document-list" :href="route('rambu.index')" :current="request()->routeIs('rambu.index')" wire:navigate>
                    {{ __('Daftar Rambu') }}
                </flux:sidebar.item>
                <flux:sidebar.item icon="clock" :href="route('audit-log')" :current="request()->routeIs('audit-log')" wire:navigate>
                    {{ __('Riwayat Aktivitas') }}
                </flux:sidebar.item>
            </flux:sidebar.group>

            <flux:sidebar.group :heading="__('Pengaturan')" expandable icon="cog-6-tooth" class="grid">
                <flux:sidebar.item icon="users" :href="route('admin.users.index')" :current="request()->routeIs('admin.users.*')" wire:navigate>
                    {{ __('Manajemen Petugas') }}
                </flux:sidebar.item>
                <flux:sidebar.item icon="tag" :href="route('admin.jenis-rambu.index')" :current="request()->routeIs('admin.jenis-rambu.*')" wire:navigate>
                    {{ __('Jenis Rambu') }}
                </flux:sidebar.item>
                <flux:sidebar.item icon="bug-ant" :href="route('admin.system-error-log.index')" :current="request()->routeIs('admin.system-error-log.*')" wire:navigate>
                    {{ __('Log Error Sistem') }}
                </flux:sidebar.item>
            </flux:sidebar.group>
        @else
            <flux:sidebar.group :heading="__('Petugas')" expandable icon="squares-2x2" class="grid">
                <flux:sidebar.item icon="home" :href="route($homeRoute)" :current="request()->routeIs('dashboard')" wire:navigate>
                    {{ __('Daftar Surat Aktif') }}
                </flux:sidebar.item>
                <flux:sidebar.item icon="clipboard-document-check" :href="route('user.spk-dikerjakan')" :current="request()->routeIs('user.spk-dikerjakan')" wire:navigate>
                    {{ __('SPK Sedang Dikerjakan') }}
                </flux:sidebar.item>
                <flux:sidebar.item icon="document-chart-bar" :href="route('user.riwayat-spk')" :current="request()->routeIs('user.riwayat-spk')" wire:navigate>
                    {{ __('Riwayat Pekerjaan Saya') }}
                </flux:sidebar.item>
                <flux:sidebar.item icon="map-pin" :href="route('user.temuan')" :current="request()->routeIs('user.temuan')" wire:navigate>
                    {{ __('Laporan Temuan Kondisi') }}
                </flux:sidebar.item>
                <flux:sidebar.item icon="map" :href="route('peta')" :current="request()->routeIs('peta')" wire:navigate>
                    {{ __('Peta Rambu') }}
                </flux:sidebar.item>
                <flux:sidebar.item icon="tag" :href="route('jenis-rambu.index')" :current="request()->routeIs('jenis-rambu.index')" wire:navigate>
                    {{ __('Jenis Rambu') }}
                </flux:sidebar.item>
                <flux:sidebar.item icon="clipboard-document-list" :href="route('rambu.index')" :current="request()->routeIs('rambu.index')" wire:navigate>
                    {{ __('Daftar Rambu') }}
                </flux:sidebar.item>
                <flux:sidebar.item icon="clock" :href="route('audit-log')" :current="request()->routeIs('audit-log')" wire:navigate>
                    {{ __('Riwayat Aktivitas') }}
                </flux:sidebar.item>
            </flux:sidebar.group>
        @endif
    </flux:sidebar.nav>

    <flux:spacer />

    <flux:sidebar.nav>
        <flux:sidebar.item icon="folder-git-2" href="https://github.com/laravel/livewire-starter-kit" target="_blank">
            {{ __('Repository') }}
        </flux:sidebar.item>

        <flux:sidebar.item icon="book-open-text" href="https://laravel.com/docs/starter-kits#livewire" target="_blank">
            {{ __('Documentation') }}
        </flux:sidebar.item>
    </flux:sidebar.nav>
</flux:sidebar>
