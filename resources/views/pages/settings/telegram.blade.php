<section class="w-full">
    @include('partials.settings-heading')

    <flux:heading class="sr-only">Notifikasi Telegram</flux:heading>

    <x-pages::settings.layout heading="Telegram" subheading="Hubungkan akun Telegram supaya notifikasi Sistem MARLIN juga masuk ke chat kamu, bukan cuma di halaman Notifikasi.">
        <div class="my-6 w-full space-y-6">
            @if ($terhubung)
                <div class="flex flex-col gap-4">
                    <flux:badge color="green" size="sm" class="w-fit">Terhubung</flux:badge>
                    <flux:text class="text-sm text-zinc-500">Notifikasi baru akan otomatis dikirim ke Telegram kamu.</flux:text>

                    <flux:button variant="danger" wire:click="putuskan" wire:confirm="Putuskan koneksi Telegram? Kamu tidak akan menerima notifikasi lewat Telegram lagi sampai menghubungkan ulang." class="self-start">
                        Putuskan Koneksi
                    </flux:button>
                </div>
            @elseif ($linkUrl)
                <div wire:poll.3s class="flex flex-col gap-4">
                    <flux:text class="text-sm text-zinc-500">
                        Buka link di bawah lalu tekan Start di Telegram. Halaman ini akan otomatis memperbarui begitu berhasil terhubung.
                    </flux:text>

                    <flux:button variant="primary" href="{{ $linkUrl }}" target="_blank" class="self-start">
                        Buka Telegram
                    </flux:button>

                    <flux:text class="text-xs text-zinc-400">Menunggu konfirmasi dari Telegram...</flux:text>
                </div>
            @else
                <div class="flex flex-col gap-4">
                    <flux:text class="text-sm text-zinc-500">Belum terhubung. Klik tombol di bawah untuk menghubungkan akun Telegram kamu.</flux:text>

                    <flux:button variant="primary" wire:click="hubungkan" class="self-start">
                        Hubungkan Telegram
                    </flux:button>
                </div>
            @endif
        </div>
    </x-pages::settings.layout>
</section>
