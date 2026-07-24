<x-layouts::auth :title="__('Autentikasi Dua Faktor')">
    <div x-data="{ recovery: false }" class="flex flex-col gap-6">
        <div class="flex flex-col gap-2 text-center">
            <flux:heading size="lg">{{ __('Autentikasi Dua Faktor') }}</flux:heading>
            <flux:subheading x-show="! recovery">
                {{ __('Masukkan kode autentikasi dari aplikator authenticator Anda.') }}
            </flux:subheading>
            <flux:subheading x-show="recovery" x-cloak>
                {{ __('Masukkan salah satu kode pemulihan darurat Anda.') }}
            </flux:subheading>
        </div>

        <form method="POST" action="{{ route('two-factor.login.store') }}" class="flex flex-col gap-6">
            @csrf

            <div x-show="! recovery">
                <flux:input name="code" label="{{ __('Kode') }}" inputmode="numeric" autofocus autocomplete="one-time-code" />
            </div>

            <div x-show="recovery" x-cloak>
                <flux:input name="recovery_code" label="{{ __('Kode Pemulihan') }}" autocomplete="one-time-code" />
            </div>

            <flux:button type="submit" variant="primary" class="w-full">
                {{ __('Lanjutkan') }}
            </flux:button>
        </form>

        <flux:button type="button" variant="ghost" x-on:click="recovery = ! recovery" class="w-full">
            <span x-show="! recovery">{{ __('Gunakan kode pemulihan') }}</span>
            <span x-show="recovery" x-cloak>{{ __('Gunakan kode autentikasi') }}</span>
        </flux:button>
    </div>
</x-layouts::auth>
