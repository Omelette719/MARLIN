    <div class="flex w-full flex-1 flex-col gap-6">
        <div>
            <flux:heading size="xl">Tambah Akun</flux:heading>
            <flux:subheading>Buat akun admin atau petugas baru. Pendaftaran mandiri tidak tersedia.</flux:subheading>
        </div>

        <flux:card class="max-w-2xl">
            <form wire:submit="save" class="flex flex-col gap-4">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <flux:input wire:model.live.debounce.500ms="name" label="Nama Lengkap" required />
                    <flux:input wire:model.live.debounce.500ms="nama_panggilan" label="Nama Panggilan" />
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <flux:input wire:model.live.debounce.500ms="nip" label="NIP" required />
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
                    <flux:input wire:model.live.debounce.500ms="tanggal_lahir" type="date" label="Tanggal Lahir" />
                    <flux:input wire:model.live.debounce.500ms="no_telepon" label="No. Telepon" />
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <flux:input wire:model="bidang" label="Bidang" />
                    <flux:input wire:model="jabatan" label="Jabatan" />
                </div>

                <flux:input wire:model="password" type="password" label="Password Awal" required description="Minimal 8 karakter. Petugas bisa menggantinya lewat halaman pengaturan." />

                <div class="flex justify-end gap-3">
                    <flux:button variant="ghost" :href="route('admin.users.index')" wire:navigate>Batal</flux:button>
                    <flux:button type="submit" variant="primary">Simpan Akun</flux:button>
                </div>
            </form>
        </flux:card>
    </div>
