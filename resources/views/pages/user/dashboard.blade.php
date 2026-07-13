<x-layouts::app :title="__('Dashboard')">
    <div class="flex w-full flex-1 flex-col gap-6">
        <div>
            <flux:heading size="xl">Selamat datang, {{ auth()->user()->name }}</flux:heading>
            <flux:subheading>Ringkasan perencanaan pemasangan rambu lalu lintas</flux:subheading>
        </div>

        <flux:card>
            <flux:heading size="lg">Belum ada data untuk ditampilkan</flux:heading>
            <flux:subheading>Hubungi admin Dishub jika Anda memerlukan akses ke data perencanaan rambu.</flux:subheading>
        </flux:card>
    </div>
</x-layouts::app>
