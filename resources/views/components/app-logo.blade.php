@props([
    'sidebar' => false,
])

<a {{ $attributes->class(
        'flex min-h-10 min-w-0 items-center gap-2.5 px-2 py-1.5 '
        . ($sidebar
            ? 'in-data-flux-sidebar-collapsed-desktop:w-10 in-data-flux-sidebar-collapsed-desktop:justify-center in-data-flux-sidebar-collapsed-desktop:px-0 in-data-flux-sidebar-collapsed-desktop:transition-opacity in-data-flux-sidebar-collapsed-desktop:in-data-flux-sidebar-active:opacity-0'
            : 'me-4')
    ) }}
>
    <img src="{{ asset('dishub-bjm.png') }}" alt="Logo Dinas Perhubungan" class="h-8 w-8 shrink-0 object-contain">

    <span @class([
        'min-w-0 leading-tight font-brand text-xl font-extrabold text-stone-800',
        'in-data-flux-sidebar-collapsed-desktop:hidden' => $sidebar,
    ])>MA<span class="text-[#004655]">RLIN</span></span>
</a>
