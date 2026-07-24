<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(JenisRambuSeeder::class);

        // User::factory(10)->create();

        User::factory()->admin()->create([
            'name' => 'Admin Dishub',
            'nama_panggilan' => 'Admin',
            'nip' => '1',
            'username' => 'admin.dishub',
            'tanggal_lahir' => '1985-01-01',
            'jenis_kelamin' => 'L',
            'bidang' => 'Sekretariat',
            'jabatan' => 'Kepala Bidang',
            'no_telepon' => '081234567890',
        ]);

        User::factory()->admin()->count(4)->create();

        User::factory()->create([
            'name' => 'Test User',
            'nama_panggilan' => 'Test',
            'nip' => '2',
            'username' => 'test.user',
            'tanggal_lahir' => '1990-03-15',
            'jenis_kelamin' => 'P',
            'bidang' => 'Bidang Lalu Lintas',
            'jabatan' => 'Staf',
            'no_telepon' => '089876543210',
        ]);
    }
}
