<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')

        {{-- This app is always light-themed, regardless of the stored/system appearance preference --}}
        <script>window.Flux && window.Flux.applyAppearance('light')</script>

        <style>
            /* The logo only shows in the header while the sidebar is hidden/collapsed */
            [data-header-brand] {
                display: none;
            }

            body:has([data-flux-sidebar][data-flux-sidebar-collapsed-desktop]) [data-header-brand],
            body:has([data-flux-sidebar][data-flux-sidebar-collapsed-mobile]) [data-header-brand] {
                display: flex;
            }
        </style>
    </head>
    <body class="h-svh overflow-hidden bg-white">
        @include('layouts.app.sidebar')
        @include('layouts.app.header')

        <flux:main class="h-full overflow-y-auto">
            {{ $slot }}
        </flux:main>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
