@props([
    'model' => null,
    'label',
    'file' => null,
    'existingUrl' => null,
    'aspect' => 'video',
    'required' => false,
    'description' => null,
    'placeholderLabel' => 'Belum ada foto',
])

@php
    $previewUrl = $file?->temporaryUrl() ?? $existingUrl;
    $aspectClass = $aspect === 'square' ? 'aspect-square' : 'aspect-video';
    $objectClass = $aspect === 'square' ? 'object-contain p-4' : 'object-cover';
@endphp

{{--
    Same fixed aspect-ratio box (16:9 for photos, 1:1 for icon-style graphics)
    used everywhere else in the app for cover images, so no upload preview
    ever looks bigger/smaller than another depending on which page it's on.
    Shown above the file input — and only once there's something to show —
    so "choose file" doesn't sit above an empty gap before anything's picked.
--}}
<div {{ $attributes }}>
    <flux:text class="mb-1.5 text-sm font-medium text-zinc-700">{{ $label }}</flux:text>

    @if ($previewUrl || $model === null)
        <div class="mb-2 w-full {{ $aspectClass }} overflow-hidden rounded-lg border border-zinc-200 bg-zinc-50">
            @if ($previewUrl)
                <img src="{{ $previewUrl }}" class="size-full {{ $objectClass }}" />
            @else
                <x-photo-placeholder class="size-full" :label="$placeholderLabel" />
            @endif
        </div>
    @endif

    @if ($model)
        <flux:input wire:model="{{ $model }}" type="file" accept="image/*" :required="$required" :description="$description" />
    @endif
</div>
