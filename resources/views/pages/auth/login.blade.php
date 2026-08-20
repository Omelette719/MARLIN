<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')

        {{-- This page always uses the light appearance, regardless of the app's stored/system preference --}}
        <script>window.Flux && window.Flux.applyAppearance('light')</script>
    </head>
    <body class="min-h-screen bg-neutral-100 antialiased">
        <div class="bg-muted flex min-h-svh flex-col items-center justify-center gap-6 p-6 md:p-10">
            <div class="flex w-full max-w-lg flex-col items-center">
                <div class="relative w-full">
                    <div class="absolute -inset-6 -z-10 rounded-4xl bg-linear-to-br from-cyan-300 via-teal-400 to-cyan-300 opacity-60 blur-2xl"></div>

                    <div class="relative w-full rounded-2xl border bg-white p-10 shadow-[0_20px_60px_-15px_rgba(15,23,42,0.25)]">
                        <div class="mb-20 flex flex-col items-center gap-8 text-center">
                            <div class="flex w-full items-center gap-4">
                                <img src="{{ asset('dishub-bjm.png') }}" alt="Logo Dinas Perhubungan" class="h-20 w-20 shrink-0 object-contain sm:h-24 sm:w-24">

                                <p class="min-w-0 text-left font-brand text-base leading-tight font-extrabold text-stone-800 sm:text-xl">
                                    Sistem Manajemen Rambu
                                    <span class="block text-[#004655]">Lalu Lintas</span>
                                </p>
                            </div>

                            <div class="flex flex-col gap-2">
                                <flux:subheading>Silakan login untuk mengakses dashboard</flux:subheading>
                            </div>
                        </div>

                        <x-auth-session-status class="mb-6 text-center" :status="session('status')" />

                        @if ($errors->any())
                            {{-- $errors->first() rather than a hardcoded string: a wrong
                            NIP/password, an admin-deactivated account, and the automatic
                            6-failed-attempts lockout below all throw different messages,
                            and every one of them deserves to actually reach the user
                            instead of being flattened into the same generic text. --}}
                            <script>
                                window.addEventListener('DOMContentLoaded', () => {
                                    setTimeout(() => {
                                        document.dispatchEvent(new CustomEvent('toast-show', {
                                            detail: {
                                                duration: 5000,
                                                slots: { text: @json($errors->first()) },
                                                dataset: { variant: 'danger' },
                                            },
                                        }));
                                    }, 50);
                                });
                            </script>
                        @endif

                        <form method="POST" action="{{ route('login') }}" class="flex flex-col gap-6">
                            @csrf

                            <flux:input
                                name="nip"
                                label="NIP"
                                type="text"
                                value="{{ old('nip') }}"
                                icon="identification"
                                placeholder="Contoh: 198501012010011001"
                                autofocus
                                autocomplete="username"
                                error:message=""
                                required
                            />

                            <flux:input
                                name="password"
                                label="Kata Sandi"
                                type="password"
                                icon="lock-closed"
                                error:message=""
                                placeholder="Masukkan kata sandi"
                                viewable
                                autocomplete="current-password"
                                required
                            />

                            <div class="flex items-center justify-between">
                                <flux:checkbox name="remember" label="Ingat saya" />

                                <flux:modal.trigger name="reset-password">
                                    <flux:link href="#" class="cursor-pointer text-sm">
                                        Reset Kata Sandi
                                    </flux:link>
                                </flux:modal.trigger>
                            </div>

                            <flux:button type="submit" variant="primary" class="mt-2 w-full justify-center">
                                Masuk
                            </flux:button>
                        </form>

                        <p class="mt-10 text-center text-xs text-stone-400">
                            &copy; {{ date('Y') }} Dinas Perhubungan. Seluruh hak cipta dilindungi.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <livewire:pages::auth.reset-password-modal />

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
