@php
    $homeRoute = auth()->user()?->isAdmin() ? 'admin.dashboard' : 'dashboard';
@endphp

<flux:sidebar sticky :collapsible="true" class="border-e border-zinc-200 bg-zinc-50">
    <flux:sidebar.header>
        <x-app-logo :sidebar="true" href="{{ route($homeRoute) }}" wire:navigate />
        <flux:sidebar.collapse />
    </flux:sidebar.header>

    <flux:sidebar.nav>
        <flux:sidebar.group :heading="__('Platform')" class="grid">
            <flux:sidebar.item icon="home" :href="route($homeRoute)" :current="request()->routeIs($homeRoute)" wire:navigate>
                {{ __('Dashboard') }}
            </flux:sidebar.item>
        </flux:sidebar.group>
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
