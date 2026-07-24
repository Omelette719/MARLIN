<?php

namespace Database\Seeders;

use App\Models\JenisRambu;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class JenisRambuSeeder extends Seeder
{
    /**
     * Seed the master data for jenis rambu.
     *
     * Each entry ships a self-colored SVG (own fill/background baked in, like
     * a real sign) used both as the reference image and as the actual marker
     * icon rendered on the peta (see PetaController + resources/js/app.js) —
     * rendered with a transparent backdrop, no synthetic wrapper shape.
     */
    public function run(): void
    {
        $items = [
            [
                'nama_jenis' => 'Rambu Peringatan',
                'spesifikasi_standar' => 'Bentuk belah ketupat, dasar kuning, memperingatkan bahaya di depan.',
                'bentuk_ikon' => 'kotak',
                'svg' => '<svg viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg"><path d="M32 4 L60 32 L32 60 L4 32 Z" fill="#FFC629" stroke="#1a1a1a" stroke-width="3"/><rect x="29" y="20" width="6" height="18" rx="3" fill="#1a1a1a"/><circle cx="32" cy="44" r="3.5" fill="#1a1a1a"/></svg>',
            ],
            [
                'nama_jenis' => 'Rambu Larangan',
                'spesifikasi_standar' => 'Bentuk bulat, dasar putih/merah, melarang gerakan tertentu.',
                'bentuk_ikon' => 'bulat',
                'svg' => '<svg viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg"><circle cx="32" cy="32" r="28" fill="white" stroke="#D8262C" stroke-width="6"/><line x1="14" y1="14" x2="50" y2="50" stroke="#D8262C" stroke-width="6"/></svg>',
            ],
            [
                'nama_jenis' => 'Rambu Perintah',
                'spesifikasi_standar' => 'Bentuk bulat, dasar biru, mewajibkan gerakan tertentu.',
                'bentuk_ikon' => 'bulat',
                'svg' => '<svg viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg"><circle cx="32" cy="32" r="28" fill="#0F5FA6"/><path d="M32 14 L48 34 H38 V50 H26 V34 H16 Z" fill="white"/></svg>',
            ],
            [
                'nama_jenis' => 'Rambu Petunjuk',
                'spesifikasi_standar' => 'Bentuk persegi, dasar hijau/biru, informasi arah/fasilitas.',
                'bentuk_ikon' => 'kotak',
                'svg' => '<svg viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg"><rect x="4" y="14" width="56" height="36" rx="4" fill="#0A7D34"/><path d="M14 26 H36 V18 L50 32 L36 46 V38 H14 Z" fill="white"/></svg>',
            ],
            [
                'nama_jenis' => 'Rambu Sementara',
                'spesifikasi_standar' => 'Digunakan untuk pekerjaan jalan/kondisi darurat, dasar oranye.',
                'bentuk_ikon' => 'kotak',
                'svg' => '<svg viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg"><path d="M32 4 L60 32 L32 60 L4 32 Z" fill="#F07A1B" stroke="#1a1a1a" stroke-width="3"/><rect x="29" y="20" width="6" height="18" rx="3" fill="#1a1a1a"/><circle cx="32" cy="44" r="3.5" fill="#1a1a1a"/></svg>',
            ],
            [
                'nama_jenis' => 'Cermin Tikungan',
                'spesifikasi_standar' => 'Cermin cembung untuk membantu visibilitas di tikungan tajam.',
                'bentuk_ikon' => 'bulat',
                'svg' => '<svg viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg"><circle cx="32" cy="32" r="28" fill="#DCE7F0" stroke="#4C5A67" stroke-width="4"/><path d="M32 16 A16 16 0 1 1 16 32" fill="none" stroke="#0F5FA6" stroke-width="4"/><path d="M32 16 L25 10 M32 16 L26 23" stroke="#0F5FA6" stroke-width="4" fill="none" stroke-linecap="round"/></svg>',
            ],
            [
                'nama_jenis' => 'Delineator',
                'spesifikasi_standar' => 'Alat pengarah tepi jalan, reflektif.',
                'bentuk_ikon' => 'kotak',
                'svg' => '<svg viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg"><rect x="26" y="8" width="12" height="42" rx="3" fill="white" stroke="#D8262C" stroke-width="2"/><rect x="26" y="8" width="12" height="10" fill="#D8262C"/><rect x="26" y="26" width="12" height="10" fill="#D8262C"/><rect x="20" y="50" width="24" height="6" rx="2" fill="#4C5A67"/></svg>',
            ],
            [
                'nama_jenis' => 'Patok Pengarah',
                'spesifikasi_standar' => 'Penanda tepi jalan pada tikungan/tanjakan.',
                'bentuk_ikon' => 'kotak',
                'svg' => '<svg viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg"><path d="M32 8 L44 30 L32 52 L20 30 Z" fill="#FFC629" stroke="#1a1a1a" stroke-width="2"/><path d="M24 24 L40 24 L36 34 L28 34 Z" fill="#1a1a1a"/></svg>',
            ],
        ];

        foreach ($items as $item) {
            $svg = $item['svg'];
            unset($item['svg']);

            $path = 'jenis-rambu/'.str($item['nama_jenis'])->slug().'.svg';
            Storage::disk('public')->put($path, $svg);
            $item['gambar_referensi'] = $path;

            JenisRambu::create($item);
        }
    }
}
