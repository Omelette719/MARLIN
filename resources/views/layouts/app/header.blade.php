@php
    $homeRoute = auth()->user()?->isAdmin() ? 'admin.dashboard' : 'dashboard';
    $unreadCount = auth()->user()?->notifikasi()->where('dibaca', false)->count() ?? 0;
@endphp

<flux:header sticky class="border-b border-zinc-200 bg-zinc-50">
    <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

    <div data-header-brand class="lg:hidden">
        <x-app-logo href="{{ route($homeRoute) }}" wire:navigate />
    </div>

    <flux:spacer />

    <div class="flex items-center gap-3">
        <flux:text class="hidden sm:block">
            {{ __('Halo,') }} <span class="font-semibold text-zinc-800">{{ auth()->user()->nama_panggilan ?: auth()->user()->name }}</span>
        </flux:text>

        <a href="{{ route('notifikasi') }}" wire:navigate class="relative inline-flex">
            <flux:button icon="bell" variant="ghost" size="sm" />
            @if ($unreadCount > 0)
                <span class="pointer-events-none absolute -top-1 -right-1 flex size-4 items-center justify-center rounded-full bg-red-600 text-[10px] leading-none font-semibold text-white">
                    {{ $unreadCount > 9 ? '9+' : $unreadCount }}
                </span>
            @endif
        </a>

        <flux:dropdown position="bottom" align="end">
            <flux:profile
                :initials="auth()->user()->initials()"
                icon-trailing="chevron-down"
            />

            <flux:menu>
                <flux:menu.radio.group>
                    <div class="p-0 text-sm font-normal">
                        <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                            <flux:avatar
                                :name="auth()->user()->name"
                                :initials="auth()->user()->initials()"
                            />

                            <div class="grid flex-1 text-start text-sm leading-tight">
                                <flux:heading class="truncate">{{ auth()->user()->name }}</flux:heading>
                                <flux:text class="truncate">NIP {{ auth()->user()->nip }}</flux:text>
                            </div>
                        </div>
                    </div>
                </flux:menu.radio.group>

                <flux:menu.separator />

                <flux:menu.radio.group>
                    <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>
                        {{ __('Settings') }}
                    </flux:menu.item>
                </flux:menu.radio.group>

                <flux:menu.separator />

                <form method="POST" action="{{ route('logout') }}" class="w-full">
                    @csrf
                    <flux:menu.item
                        as="button"
                        type="submit"
                        icon="arrow-right-start-on-rectangle"
                        class="w-full cursor-pointer"
                        data-test="logout-button"
                    >
                        {{ __('Log out') }}
                    </flux:menu.item>
                </form>
            </flux:menu>
        </flux:dropdown>
    </div>
</flux:header>
