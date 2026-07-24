<x-layouts::auth :title="__('Konfirmasi Kata Sandi')">
    <div class="flex flex-col gap-6">
        <div class="flex flex-col gap-2 text-center">
            <flux:heading size="lg">{{ __('Konfirmasi Kata Sandi') }}</flux:heading>
            <flux:subheading>{{ __('Ini adalah area sensitif. Masukkan kata sandi Anda untuk melanjutkan.') }}</flux:subheading>
        </div>

        <form method="POST" action="{{ route('password.confirm') }}" class="flex flex-col gap-6">
            @csrf

            <flux:input
                type="password"
                name="password"
                label="{{ __('Kata Sandi') }}"
                required
                autofocus
                autocomplete="current-password"
                viewable
            />

            <flux:button type="submit" variant="primary" class="w-full">
                {{ __('Konfirmasi') }}
            </flux:button>
        </form>
    </div>
</x-layouts::auth>
