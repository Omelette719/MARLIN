@props([
    'photos' => [],
    'intervalMs' => 4000,
])

@php
    $photos = collect($photos)->filter()->values();
@endphp

@if ($photos->isEmpty())
    <div {{ $attributes->merge(['class' => 'bg-zinc-100']) }}>
        <x-photo-placeholder class="size-full" />
    </div>
@elseif ($photos->count() === 1)
    <div {{ $attributes->merge(['class' => 'overflow-hidden bg-zinc-100']) }}>
        <img src="{{ $photos->first() }}" class="size-full object-cover" />
    </div>
@else
    <div
        {{ $attributes->merge(['class' => 'relative overflow-hidden bg-zinc-100']) }}
        x-data="{ index: 0 }"
        x-init="let id = setInterval(() => index = (index + 1) % {{ $photos->count() }}, {{ $intervalMs }}); $cleanup(() => clearInterval(id))"
    >
        @foreach ($photos as $i => $url)
            <img
                src="{{ $url }}"
                class="absolute inset-0 size-full object-cover transition-opacity duration-1000 ease-in-out"
                :class="index === {{ $i }} ? 'opacity-100' : 'opacity-0'"
            />
        @endforeach
    </div>
@endif
