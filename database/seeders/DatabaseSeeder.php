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
        // User::factory(10)->create();

        User::factory()->admin()->create([
            'name' => 'Admin Dishub',
            'nama_panggilan' => 'Admin',
            'nip' => '198501012010011001',
            'tanggal_lahir' => '1985-01-01',
            'jenis_kelamin' => 'L',
            'bidang' => 'Sekretariat',
            'jabatan' => 'Kepala Bidang',
            'no_telepon' => '081234567890',
        ]);

        User::factory()->create([
            'name' => 'Test User',
            'nama_panggilan' => 'Test',
            'nip' => '199003152015031002',
            'tanggal_lahir' => '1990-03-15',
            'jenis_kelamin' => 'P',
            'bidang' => 'Bidang Lalu Lintas',
            'jabatan' => 'Staf',
            'no_telepon' => '089876543210',
        ]);
    }
}
