@props([
    'fallback',
    'icon' => null,
])

@php
    // @js() doesn't expand here — flux:button's custom tag compiler runs
    // before Blade's own directive pass, so it reaches the browser as the
    // literal text "@js(...)" instead of a JSON value. Pre-computing it and
    // interpolating with {{ }} sidesteps that (the HTML-entity escaping
    // {{ }} adds is transparent to the browser/Alpine either way).
    $fallbackJs = \Illuminate\Support\Js::from($fallback);
@endphp

{{--
    Prefers real browser back-navigation (returns to whatever page was
    actually open before, matching multi-entry pages like a validasi/rambu
    detail reachable from a list, a notification, or another detail page)
    over the hardcoded $fallback route — see marlinGoBack() in app.js.
    :href="$fallback" is kept so the button stays a real, right-clickable
    link (open in new tab, screen readers) and still works if JS is
    unavailable or there's genuinely no in-app page to go back to.
--}}
<flux:button
    variant="ghost"
    :icon="$icon"
    :href="$fallback"
    wire:navigate
    x-data
    x-on:click.prevent="marlinGoBack({{ $fallbackJs }})"
    {{ $attributes }}
>
    {{ $slot }}
</flux:button>
