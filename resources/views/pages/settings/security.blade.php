@php
    use Illuminate\Validation\Rules\Password;
@endphp

<section class="w-full">
    @include('partials.settings-heading')

    <flux:heading class="sr-only">{{ __('Pengaturan keamanan') }}</flux:heading>

    <x-pages::settings.layout :heading="__('Ubah Kata Sandi')" :subheading="__('Pastikan akunmu memakai kata sandi yang panjang dan acak agar tetap aman')">
        <form method="POST" wire:submit="updatePassword" class="mt-6 space-y-6">
            <flux:input
                wire:model="current_password"
                :label="__('Kata Sandi Saat Ini')"
                type="password"
                required
                autocomplete="current-password"
                viewable
            />
            <flux:input
                wire:model="password"
                :label="__('Kata Sandi Baru')"
                type="password"
                required
                autocomplete="new-password"
                passwordrules="{{ Password::defaults()->toPasswordRulesString() }}"
                viewable
            />
            <flux:input
                wire:model="password_confirmation"
                :label="__('Konfirmasi Kata Sandi')"
                type="password"
                required
                autocomplete="new-password"
                passwordrules="{{ Password::defaults()->toPasswordRulesString() }}"
                viewable
            />

            <div class="flex items-center gap-4">
                <flux:button variant="primary" type="submit" data-test="update-password-button">
                    {{ __('Simpan') }}
                </flux:button>
            </div>
        </form>

        @if ($canManageTwoFactor)
            <section class="mt-12">
                <flux:heading>{{ __('Autentikasi Dua Faktor') }}</flux:heading>
                <flux:subheading>{{ __('Kelola pengaturan autentikasi dua faktor akunmu') }}</flux:subheading>

                <div class="flex flex-col w-full mx-auto space-y-6 text-sm" wire:cloak>
                    @if ($twoFactorEnabled)
                        <div class="space-y-4">
                            <flux:text>
                                {{ __('Kamu akan diminta memasukkan pin acak yang aman saat login, yang bisa didapat dari aplikasi pendukung TOTP di ponselmu.') }}
                            </flux:text>

                            <div class="flex justify-start">
                                <flux:button
                                    variant="danger"
                                    wire:click="disable"
                                >
                                    {{ __('Nonaktifkan 2FA') }}
                                </flux:button>
                            </div>

                            <livewire:pages::settings.two-factor.recovery-codes :$requiresConfirmation />
                        </div>
                    @else
                        <div class="space-y-4">
                            <flux:text variant="subtle">
                                {{ __('Setelah autentikasi dua faktor diaktifkan, kamu akan diminta memasukkan pin aman saat login. Pin ini bisa didapat dari aplikasi pendukung TOTP di ponselmu.') }}
                            </flux:text>

                            <flux:modal.trigger name="two-factor-setup-modal">
                                <flux:button
                                    variant="primary"
                                    wire:click="$dispatch('start-two-factor-setup')"
                                >
                                    {{ __('Aktifkan 2FA') }}
                                </flux:button>
                            </flux:modal.trigger>

                            <livewire:pages::settings.two-factor-setup-modal :requires-confirmation="$requiresConfirmation" />
                        </div>
                    @endif
                </div>
            </section>
        @endif
    </x-pages::settings.layout>
</section>
