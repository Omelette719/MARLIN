    <div class="flex w-full flex-1 flex-col gap-6">
        <div>
            <flux:heading size="xl">Edit Akun</flux:heading>
            <flux:subheading>{{ $user->name }}</flux:subheading>
        </div>

        <flux:card class="max-w-2xl">
            <form wire:submit="save" class="flex flex-col gap-4">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <flux:input wire:model="name" label="Nama Lengkap" required />
                    <flux:input wire:model="nama_panggilan" label="Nama Panggilan" />
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <flux:input wire:model="nip" label="NIP" required />
                    <flux:input wire:model="username" label="Username" description="Opsional" />
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <flux:select wire:model="role" label="Role" required>
                        @foreach ($roleOptions as $r)
                            <flux:select.option value="{{ $r->value }}">{{ $r->label() }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:select wire:model="jenis_kelamin" label="Jenis Kelamin" placeholder="Pilih jenis kelamin">
                        @foreach ($kelaminOptions as $k)
                            <flux:select.option value="{{ $k->value }}">{{ $k->label() }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <flux:input wire:model="tanggal_lahir" type="date" label="Tanggal Lahir" />
                    <flux:input wire:model="no_telepon" label="No. Telepon" />
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <flux:input wire:model="bidang" label="Bidang" />
                    <flux:input wire:model="jabatan" label="Jabatan" />
                </div>

                <flux:checkbox wire:model="aktif" label="Akun aktif" description="Nonaktifkan untuk mencabut akses login tanpa menghapus data." />

                <flux:input wire:model="password" type="password" label="Password Baru" description="Kosongkan jika tidak ingin mengganti password." />

                <div class="flex justify-end gap-3">
                    <flux:button variant="ghost" :href="route('admin.users.index')" wire:navigate>Batal</flux:button>
                    <flux:button type="submit" variant="primary">Simpan Perubahan</flux:button>
                </div>
            </form>
        </flux:card>
    </div>
