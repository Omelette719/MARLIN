@props([
    'wireModel',
    'options',
    'placeholder' => 'Ketik untuk mencari nama...',
    'label' => null,
])

<div
    x-data="{
        open: false,
        search: '',
        options: @js($options),
        values: $wire.entangle('{{ $wireModel }}'),
        get filtered() {
            const chosen = this.values.map(String)
            const q = this.search.toLowerCase()
            return this.options.filter(o => ! chosen.includes(String(o.value)) && (! q || o.label.toLowerCase().includes(q)))
        },
        labelFor(id) {
            const found = this.options.find(o => String(o.value) === String(id))
            return found ? found.label : id
        },
        add(opt) {
            this.values.push(opt.value)
            this.search = ''
        },
        remove(id) {
            this.values = this.values.filter(v => String(v) !== String(id))
        },
    }"
    x-on:click.outside="open = false"
    {{ $attributes->class('relative') }}
>
    @if ($label)
        <flux:text class="mb-1.5 block text-sm font-medium text-zinc-700">{{ $label }}</flux:text>
    @endif

    <div class="flex min-h-10 flex-wrap items-center gap-1.5 rounded-lg border border-zinc-200 border-b-zinc-300/80 bg-white px-2 py-1.5 shadow-xs">
        <template x-for="id in values" :key="id">
            <span class="inline-flex items-center gap-1 rounded-full bg-zinc-100 py-1 pr-1 pl-2.5 text-xs font-medium text-zinc-700">
                <span x-text="labelFor(id)"></span>
                <button type="button" x-on:click="remove(id)" class="rounded-full p-0.5 hover:bg-zinc-200">
                    <flux:icon icon="x-mark" class="size-3" />
                </button>
            </span>
        </template>

        <input
            type="text"
            x-model="search"
            x-on:focus="open = true"
            placeholder="{{ $placeholder }}"
            class="min-w-32 flex-1 border-0 p-1 text-sm text-zinc-700 focus:ring-0"
            autocomplete="off"
        />
    </div>

    <div
        x-show="open"
        x-cloak
        class="absolute z-20 mt-1 max-h-56 w-full overflow-auto rounded-lg border border-zinc-200 bg-white py-1 shadow-lg"
    >
        <template x-for="opt in filtered" :key="opt.value">
            <div
                x-on:click="add(opt)"
                x-text="opt.label"
                class="cursor-pointer px-3 py-2 text-sm text-zinc-700 hover:bg-zinc-100"
            ></div>
        </template>
        <div x-show="filtered.length === 0" class="px-3 py-2 text-sm text-zinc-400">Tidak ada nama lain</div>
    </div>

    <flux:error name="{{ $wireModel }}" />
</div>
