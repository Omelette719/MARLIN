@php
    // Dummy data sementara — nanti diganti dengan query ke model rencana pemasangan rambu.
    // 1 rencana (surat pengantar) mencakup 1 wilayah, dan bisa berisi banyak rambu & titik lokasi.
    $stats = [
        ['label' => 'Rambu Terpasang', 'value' => 128, 'icon' => 'check-circle', 'color' => 'green'],
        ['label' => 'Belum Terpasang', 'value' => 34, 'icon' => 'clock', 'color' => 'amber'],
        ['label' => 'Total Keseluruhan', 'value' => 162, 'icon' => 'map', 'color' => 'blue'],
        ['label' => 'Progres Pemasangan', 'value' => '79%', 'icon' => 'chart-bar', 'color' => 'violet'],
    ];

    $rencana = collect([
        ['no_surat' => '005/DISHUB-BJM/I/2026', 'wilayah' => 'Banjarmasin Utara', 'jumlah_rambu' => 15, 'jumlah_titik' => 10, 'terpasang' => 15, 'tahun' => 2026],
        ['no_surat' => '012/DISHUB-BJM/II/2026', 'wilayah' => 'Banjarmasin Selatan', 'jumlah_rambu' => 22, 'jumlah_titik' => 14, 'terpasang' => 9, 'tahun' => 2026],
        ['no_surat' => '018/DISHUB-BJM/III/2026', 'wilayah' => 'Banjarmasin Timur', 'jumlah_rambu' => 18, 'jumlah_titik' => 12, 'terpasang' => 0, 'tahun' => 2026],
        ['no_surat' => '024/DISHUB-BJM/IV/2026', 'wilayah' => 'Banjarmasin Barat', 'jumlah_rambu' => 9, 'jumlah_titik' => 6, 'terpasang' => 9, 'tahun' => 2026],
        ['no_surat' => '031/DISHUB-BJM/V/2026', 'wilayah' => 'Banjarmasin Tengah', 'jumlah_rambu' => 27, 'jumlah_titik' => 18, 'terpasang' => 12, 'tahun' => 2026],
        ['no_surat' => '007/DISHUB-BJM/I/2027', 'wilayah' => 'Banjarmasin Utara', 'jumlah_rambu' => 20, 'jumlah_titik' => 13, 'terpasang' => 0, 'tahun' => 2027],
    ])->map(function ($item) {
        $item['status'] = match (true) {
            $item['terpasang'] === 0 => 'Belum Dimulai',
            $item['terpasang'] === $item['jumlah_rambu'] => 'Selesai',
            default => 'Sedang Berjalan',
        };

        return $item;
    });
@endphp

<x-layouts::app :title="__('Dashboard')">
    <div class="flex w-full flex-1 flex-col gap-6">
        <div>
            <flux:heading size="xl">Dashboard</flux:heading>
            <flux:subheading>Ringkasan perencanaan pemasangan rambu lalu lintas</flux:subheading>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($stats as $stat)
                <flux:card class="flex items-center gap-4">
                    <div @class([
                        'flex size-12 shrink-0 items-center justify-center rounded-full',
                        'bg-green-100 text-green-600' => $stat['color'] === 'green',
                        'bg-amber-100 text-amber-600' => $stat['color'] === 'amber',
                        'bg-blue-100 text-blue-600' => $stat['color'] === 'blue',
                        'bg-violet-100 text-violet-600' => $stat['color'] === 'violet',
                    ])>
                        <flux:icon :icon="$stat['icon']" class="size-6" />
                    </div>

                    <div class="min-w-0">
                        <flux:text class="text-sm text-zinc-500">{{ $stat['label'] }}</flux:text>
                        <flux:heading size="xl">{{ $stat['value'] }}</flux:heading>
                    </div>
                </flux:card>
            @endforeach
        </div>

        <flux:card class="flex-1">
            <div class="mb-4">
                <flux:heading size="lg">Perencanaan Pemasangan Rambu Lalu Lintas</flux:heading>
                <flux:subheading>Daftar rencana (surat pengantar) pemasangan rambu per wilayah — setiap rencana dapat mencakup banyak rambu & titik lokasi</flux:subheading>
            </div>

            <flux:table>
                <flux:table.columns>
                    <flux:table.column>No</flux:table.column>
                    <flux:table.column>No. Surat Pengantar</flux:table.column>
                    <flux:table.column>Wilayah</flux:table.column>
                    <flux:table.column>Tahun</flux:table.column>
                    <flux:table.column align="center">Jumlah Rambu</flux:table.column>
                    <flux:table.column align="center">Titik Lokasi</flux:table.column>
                    <flux:table.column>Status</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @foreach ($rencana as $i => $item)
                        <flux:table.row>
                            <flux:table.cell>{{ $i + 1 }}</flux:table.cell>
                            <flux:table.cell variant="strong">{{ $item['no_surat'] }}</flux:table.cell>
                            <flux:table.cell>{{ $item['wilayah'] }}</flux:table.cell>
                            <flux:table.cell>{{ $item['tahun'] }}</flux:table.cell>
                            <flux:table.cell align="center">{{ $item['jumlah_rambu'] }}</flux:table.cell>
                            <flux:table.cell align="center">{{ $item['jumlah_titik'] }}</flux:table.cell>
                            <flux:table.cell>
                                @if ($item['status'] === 'Selesai')
                                    <flux:badge color="green" size="sm">Selesai ({{ $item['terpasang'] }}/{{ $item['jumlah_rambu'] }})</flux:badge>
                                @elseif ($item['status'] === 'Sedang Berjalan')
                                    <flux:badge color="blue" size="sm">Berjalan ({{ $item['terpasang'] }}/{{ $item['jumlah_rambu'] }})</flux:badge>
                                @else
                                    <flux:badge size="sm">Belum Dimulai</flux:badge>
                                @endif
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        </flux:card>
    </div>
</x-layouts::app>
