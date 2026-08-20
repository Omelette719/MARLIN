@php
    use Illuminate\Support\Facades\Auth;
@endphp

<section class="w-full">
    @include('partials.settings-heading')

    <flux:heading class="sr-only">{{ __('Pengaturan profil') }}</flux:heading>

    <x-pages::settings.layout :heading="__('Profil')" :subheading="__('Lihat data akunmu dan perbarui data yang bisa diubah')">
        <form wire:submit="updateProfileInformation" class="my-6 w-full space-y-6">
            <flux:input wire:model="name" :label="__('Nama Lengkap')" type="text" required autofocus autocomplete="name" />

            <flux:input wire:model="nama_panggilan" :label="__('Nama Panggilan')" type="text" />

            <flux:input wire:model="no_telepon" :label="__('No. Telepon')" type="text" />

            <div class="flex items-center gap-4">
                <flux:button variant="primary" type="submit" data-test="update-profile-button">
                    {{ __('Simpan') }}
                </flux:button>
            </div>
        </form>

        <flux:separator class="my-6" />

        <div class="space-y-1">
            <flux:heading size="sm">{{ __('Data Lainnya') }}</flux:heading>
            <flux:subheading>{{ __('Data berikut hanya bisa diubah oleh admin.') }}</flux:subheading>
        </div>

        <div class="my-6 w-full space-y-6">
            <flux:input :label="__('NIP')" type="text" value="{{ Auth::user()->nip }}" disabled />

            <flux:input :label="__('Username')" type="text" value="{{ Auth::user()->username ?? '-' }}" disabled />

            <flux:input :label="__('Peran')" type="text" value="{{ Auth::user()->role->label() }}" disabled />

            <flux:input :label="__('Tanggal Lahir')" type="text" value="{{ Auth::user()->tanggal_lahir?->format('d-m-Y') ?? '-' }}" disabled />

            <flux:input :label="__('Jenis Kelamin')" type="text" value="{{ Auth::user()->jenis_kelamin?->label() ?? '-' }}" disabled />

            <flux:input :label="__('Bidang')" type="text" value="{{ Auth::user()->bidang ?? '-' }}" disabled />

            <flux:input :label="__('Jabatan')" type="text" value="{{ Auth::user()->jabatan ?? '-' }}" disabled />
        </div>

        <livewire:pages::settings.delete-user-form />
    </x-pages::settings.layout>
</section>
