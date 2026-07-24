@props([
    'wireModel',
    'options',
    'placeholder' => 'Pilih...',
    'label' => null,
    'required' => false,
])

<div
    x-data="{
        open: false,
        search: '',
        options: @js($options),
        value: $wire.entangle('{{ $wireModel }}'),
        get filtered() {
            if (! this.search) return this.options
            const q = this.search.toLowerCase()
            return this.options.filter(o => o.label.toLowerCase().includes(q))
        },
        get selectedLabel() {
            const found = this.options.find(o => String(o.value) === String(this.value))
            return found ? found.label : ''
        },
        toggle() {
            this.open = ! this.open
            if (this.open) {
                this.search = ''
                this.$nextTick(() => this.$refs.searchInput?.focus())
            }
        },
        pick(opt) {
            this.value = opt.value
            this.open = false
        },
    }"
    x-on:click.outside="open = false"
    {{ $attributes->class('relative') }}
>
    @if ($label)
        <flux:text class="mb-1.5 block text-sm font-medium text-zinc-700">
            {{ $label }}@if ($required)<span class="text-red-500">*</span>@endif
        </flux:text>
    @endif

    <button
        type="button"
        x-on:click="toggle()"
        class="flex h-10 w-full items-center justify-between rounded-lg border border-zinc-200 border-b-zinc-300/80 bg-white px-3 text-sm shadow-xs focus:outline-hidden"
    >
        <span x-show="! open" x-text="selectedLabel || '{{ $placeholder }}'" :class="selectedLabel ? 'text-zinc-700' : 'text-zinc-400'" class="truncate"></span>
        <input
            x-ref="searchInput"
            x-show="open"
            x-model="search"
            type="text"
            placeholder="Ketik untuk mencari..."
            class="w-full border-0 p-0 text-sm text-zinc-700 focus:ring-0"
            autocomplete="off"
            x-on:click.stop
        />
        <flux:icon icon="chevron-down" class="size-4 shrink-0 text-zinc-400" />
    </button>

    <div
        x-show="open"
        x-cloak
        class="absolute z-20 mt-1 max-h-56 w-full overflow-auto rounded-lg border border-zinc-200 bg-white py-1 shadow-lg"
    >
        <template x-for="opt in filtered" :key="opt.value">
            <div
                x-on:click="pick(opt)"
                x-text="opt.label"
                class="cursor-pointer px-3 py-2 text-sm text-zinc-700 hover:bg-zinc-100"
            ></div>
        </template>
        <div x-show="filtered.length === 0" class="px-3 py-2 text-sm text-zinc-400">Tidak ditemukan</div>
    </div>
</div>
