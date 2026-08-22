    @php
        use App\Enums\Role;
    @endphp

    <div class="flex w-full flex-1 flex-col gap-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <flux:heading size="xl">Manajemen Petugas</flux:heading>
                <flux:subheading>Kelola akun admin dan petugas lapangan.</flux:subheading>
            </div>

            <flux:button variant="primary" icon="plus" :href="route('admin.users.create')" wire:navigate>
                Tambah Akun
            </flux:button>
        </div>

        <flux:input wire:model.live.debounce.400ms="search" placeholder="Cari nama atau NIP..." icon="magnifying-glass" class="max-w-sm" />

        <flux:card class="flex-1">
            <x-table-scroll-hint />
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Nama</flux:table.column>
                    <flux:table.column>NIP</flux:table.column>
                    <flux:table.column>Bidang / Jabatan</flux:table.column>
                    <flux:table.column>Role</flux:table.column>
                    <flux:table.column>Status</flux:table.column>
                    <flux:table.column align="end">Aksi</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse ($users as $user)
                        <flux:table.row>
                            <flux:table.cell variant="strong">{{ $user->name }}</flux:table.cell>
                            <flux:table.cell>{{ $user->nip }}</flux:table.cell>
                            <flux:table.cell>{{ $user->bidang }}, {{ $user->jabatan }}</flux:table.cell>
                            <flux:table.cell>
                                <flux:badge size="sm" :color="$user->role === Role::Admin ? 'blue' : 'zinc'">
                                    {{ $user->role->label() }}
                                </flux:badge>
                            </flux:table.cell>
                            <flux:table.cell>
                                <flux:badge size="sm" :color="$user->aktif ? 'green' : 'zinc'">
                                    {{ $user->aktif ? 'Aktif' : 'Nonaktif' }}
                                </flux:badge>
                            </flux:table.cell>
                            <flux:table.cell align="end">
                                <flux:button size="sm" variant="ghost" :href="route('admin.users.edit', $user)" wire:navigate>
                                    Edit
                                </flux:button>
                                <flux:button size="sm" variant="ghost" wire:click="toggleAktif({{ $user->id }})">
                                    {{ $user->aktif ? 'Nonaktifkan' : 'Aktifkan' }}
                                </flux:button>
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="6" class="text-center text-zinc-500">
                                Tidak ada akun ditemukan.
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>

            <div class="mt-4">
                {{ $users->links() }}
            </div>
        </flux:card>
    </div>
