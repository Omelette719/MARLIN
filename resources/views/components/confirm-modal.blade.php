@props([
    'name',
    'heading',
    'text',
    'action',
    'confirmLabel' => 'Ya, Lanjutkan',
    'cancelLabel' => 'Batal',
    'tone' => 'danger',
    'icon' => null,
])

@php
    $icon ??= $tone === 'danger' ? 'exclamation-triangle' : 'question-mark-circle';
    $iconClasses = $tone === 'danger' ? 'bg-red-100 text-red-600' : 'bg-blue-100 text-blue-600';
    $buttonVariant = $tone === 'danger' ? 'danger' : 'primary';
@endphp

<flux:modal name="{{ $name }}" class="max-w-md">
    <div class="flex flex-col gap-5">
        <div class="flex items-start gap-4">
            <div class="flex size-11 shrink-0 items-center justify-center rounded-full {{ $iconClasses }}">
                <flux:icon :icon="$icon" class="size-6" />
            </div>
            <div>
                <flux:heading size="lg">{{ $heading }}</flux:heading>
                <flux:subheading>{{ $text }}</flux:subheading>
            </div>
        </div>

        <div class="flex justify-end gap-3">
            <flux:modal.close>
                <flux:button variant="ghost">{{ $cancelLabel }}</flux:button>
            </flux:modal.close>

            <flux:button variant="{{ $buttonVariant }}" wire:click="{{ $action }}">
                {{ $confirmLabel }}
            </flux:button>
        </div>
    </div>
</flux:modal>
