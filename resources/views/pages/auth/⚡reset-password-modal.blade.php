<?php

use App\Concerns\PasswordValidationRules;
use App\Models\User;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Livewire\Attributes\Locked;
use Livewire\Component;

new class extends Component {
    use PasswordValidationRules;

    #[Locked]
    public bool $verified = false;

    #[Locked]
    public ?int $verifiedUserId = null;

    public string $nip = '';
    public string $password = '';

    public string $new_password = '';
    public string $new_password_confirmation = '';

    /**
     * Step 1: verify the user knows their NIP and current password.
     */
    public function verifyIdentity(): void
    {
        $this->validate([
            'nip' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $throttleKey = Str::transliterate(Str::lower($this->nip).'|'.request()->ip());

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $this->addError('password', __('Terlalu banyak percobaan. Coba lagi dalam :seconds detik.', [
                'seconds' => RateLimiter::availableIn($throttleKey),
            ]));

            return;
        }

        if (! Auth::validate(['nip' => $this->nip, 'password' => $this->password])) {
            RateLimiter::hit($throttleKey);

            $this->addError('password', __('NIP atau kata sandi yang Anda masukkan salah.'));

            return;
        }

        RateLimiter::clear($throttleKey);

        $this->verifiedUserId = User::where('nip', $this->nip)->value('id');
        $this->verified = true;
        $this->password = '';
    }

    /**
     * Step 2: set the new password now that identity is verified.
     */
    public function updatePassword(): void
    {
        if (! $this->verified || ! $this->verifiedUserId) {
            $this->resetState();

            return;
        }

        $this->validate([
            'new_password' => $this->passwordRules(),
        ]);

        User::whereKey($this->verifiedUserId)->first()?->update([
            'password' => $this->new_password,
        ]);

        Flux::toast(variant: 'success', text: __('Kata sandi berhasil diubah. Silakan masuk dengan kata sandi baru Anda.'));

        $this->resetState();

        $this->dispatch('modal-close', name: 'reset-password');
    }

    /**
     * Reset the wizard back to step 1 (e.g. when the modal is dismissed).
     */
    public function resetState(): void
    {
        $this->reset(['nip', 'password', 'new_password', 'new_password_confirmation', 'verified', 'verifiedUserId']);
        $this->resetErrorBag();
    }
}; ?>

<flux:modal name="reset-password" class="w-full max-w-md" wire:close="resetState">
    @if (! $verified)
        <form wire:submit="verifyIdentity" class="flex flex-col gap-6">
            <div>
                <flux:heading size="lg">{{ __('Reset Kata Sandi') }}</flux:heading>
                <flux:subheading>{{ __('Langkah 1 dari 2 — masukkan NIP dan kata sandi Anda saat ini untuk melanjutkan.') }}</flux:subheading>
            </div>

            <flux:input
                wire:model="nip"
                :label="__('NIP')"
                type="text"
                icon="identification"
                placeholder="Contoh: 198501012010011001"
                autofocus
                autocomplete="username"
                required
            />

            <flux:input
                wire:model="password"
                :label="__('Kata Sandi Saat Ini')"
                type="password"
                icon="lock-closed"
                viewable
                autocomplete="current-password"
                required
            />

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Batal') }}</flux:button>
                </flux:modal.close>

                <flux:button type="submit" variant="primary" color="blue">
                    {{ __('Lanjutkan') }}
                </flux:button>
            </div>
        </form>
    @else
        <form wire:submit="updatePassword" class="flex flex-col gap-6">
            <div>
                <flux:heading size="lg">{{ __('Buat Kata Sandi Baru') }}</flux:heading>
                <flux:subheading>{{ __('Langkah 2 dari 2 — identitas Anda terverifikasi. Masukkan kata sandi baru Anda.') }}</flux:subheading>
            </div>

            <flux:input
                wire:model="new_password"
                :label="__('Kata Sandi Baru')"
                type="password"
                icon="lock-closed"
                viewable
                autocomplete="new-password"
                autofocus
                required
            />

            <flux:input
                wire:model="new_password_confirmation"
                :label="__('Konfirmasi Kata Sandi Baru')"
                type="password"
                icon="lock-closed"
                viewable
                autocomplete="new-password"
                required
            />

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Batal') }}</flux:button>
                </flux:modal.close>

                <flux:button type="submit" variant="primary" color="blue">
                    {{ __('Simpan Kata Sandi') }}
                </flux:button>
            </div>
        </form>
    @endif
</flux:modal>
