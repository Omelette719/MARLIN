<?php

namespace Database\Seeders;

use App\Models\JenisRambu;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class JenisRambuSeeder extends Seeder
{
    // Generic per-kategori description, reused across every real-photo entry
    // in that kategori below — mirrors the wording this seeder already used
    // for the old placeholder category icons, so it stays consistent even
    // though there's no room to write a bespoke paragraph for all 76 signs.
    private const KATEGORI = [
        'peringatan' => ['bentuk_ikon' => 'kotak', 'deskripsi' => 'Bentuk belah ketupat, dasar kuning, memperingatkan bahaya di depan.'],
        'larangan' => ['bentuk_ikon' => 'bulat', 'deskripsi' => 'Bentuk bulat, dasar putih/merah, melarang atau membatasi gerakan tertentu.'],
        'perintah' => ['bentuk_ikon' => 'bulat', 'deskripsi' => 'Bentuk bulat, dasar biru, mewajibkan gerakan tertentu.'],
        'petunjuk' => ['bentuk_ikon' => 'kotak', 'deskripsi' => 'Bentuk persegi, dasar hijau/biru, informasi arah/fasilitas.'],
        'perlengkapan' => ['bentuk_ikon' => 'kotak', 'deskripsi' => 'Perlengkapan jalan pendukung keselamatan dan kelancaran lalu lintas.'],
    ];

    /**
     * Seed the master data for jenis rambu: the 76 real signs photographed
     * into database/seeders/images/jenis-rambu/, each copied into
     * storage/app/public/jenis-rambu/ at seed time (the same place uploads
     * via the Jenis Rambu admin page land). No hand-drawn placeholder icons
     * — those were only ever a stand-in before real photos existed.
     */
    public function run(): void
    {
        // 'file' is the source filename inside database/seeders/images/jenis-rambu/.
        // nama_jenis deliberately mirrors that filename as closely as
        // possible (only case/spacing normalized, not reworded) — per
        // request, so the seeded name is always recognizable against the
        // source image it came from.
        $foto = [
            ['file' => '20 Max.png', 'nama_jenis' => '20 Max', 'kategori' => 'larangan'],
            ['file' => '30 Max.png', 'nama_jenis' => '30 Max', 'kategori' => 'larangan'],
            ['file' => '40 Max.png', 'nama_jenis' => '40 Max', 'kategori' => 'larangan'],
            ['file' => 'Arah Belok Kanan.png', 'nama_jenis' => 'Arah Belok Kanan', 'kategori' => 'perintah'],
            ['file' => 'Arah Belok Kiri.png', 'nama_jenis' => 'Arah Belok Kiri', 'kategori' => 'perintah'],
            ['file' => 'Arah.png', 'nama_jenis' => 'Arah', 'kategori' => 'perintah'],
            ['file' => 'Beban Maks 3,5 Ton.png', 'nama_jenis' => 'Beban Maks 3,5 Ton', 'kategori' => 'larangan'],
            ['file' => 'Beban Maks 6 Ton.png', 'nama_jenis' => 'Beban Maks 6 Ton', 'kategori' => 'larangan'],
            ['file' => 'Beban Maks 8 Ton.png', 'nama_jenis' => 'Beban Maks 8 Ton', 'kategori' => 'larangan'],
            ['file' => 'Bundaran.png', 'nama_jenis' => 'Bundaran', 'kategori' => 'perintah'],
            ['file' => 'Cermin Tikungan.png', 'nama_jenis' => 'Cermin Tikungan', 'kategori' => 'perlengkapan', 'bentuk_ikon' => 'bulat'],
            ['file' => 'Chevron Kanan.png', 'nama_jenis' => 'Chevron Kanan', 'kategori' => 'perlengkapan'],
            ['file' => 'Dilarang Parkir.png', 'nama_jenis' => 'Dilarang Parkir', 'kategori' => 'larangan'],
            ['file' => 'Gereja.png', 'nama_jenis' => 'Gereja', 'kategori' => 'petunjuk'],
            ['file' => 'Halte Bus.png', 'nama_jenis' => 'Halte Bus', 'kategori' => 'petunjuk'],
            ['file' => 'Jalur Sepeda.png', 'nama_jenis' => 'Jalur Sepeda', 'kategori' => 'petunjuk'],
            ['file' => 'Larangan Becak Masuk.png', 'nama_jenis' => 'Larangan Becak Masuk', 'kategori' => 'larangan'],
            ['file' => 'Larangan Belok Kanan.png', 'nama_jenis' => 'Larangan Belok Kanan', 'kategori' => 'larangan'],
            ['file' => 'Larangan Belok Kiri.png', 'nama_jenis' => 'Larangan Belok Kiri', 'kategori' => 'larangan'],
            ['file' => 'Larangan Jalan Terus Prioritas.png', 'nama_jenis' => 'Larangan Jalan Terus Prioritas', 'kategori' => 'larangan'],
            ['file' => 'Larangan Masuk bagi Kendaraan tinggi dari 30 m.png', 'nama_jenis' => 'Larangan Masuk bagi Kendaraan Tinggi dari 30 M', 'kategori' => 'larangan'],
            ['file' => 'Larangan Masuk.png', 'nama_jenis' => 'Larangan Masuk', 'kategori' => 'larangan'],
            ['file' => 'Larangan Mendahului.png', 'nama_jenis' => 'Larangan Mendahului', 'kategori' => 'larangan'],
            ['file' => 'Larangan Putar Balik dan Belok Kanan.png', 'nama_jenis' => 'Larangan Putar Balik dan Belok Kanan', 'kategori' => 'larangan'],
            ['file' => 'Larangan Putar Balik.png', 'nama_jenis' => 'Larangan Putar Balik', 'kategori' => 'larangan'],
            ['file' => 'Larangan Stop.png', 'nama_jenis' => 'Larangan Stop', 'kategori' => 'larangan'],
            ['file' => 'Larangan Truck Masuk.png', 'nama_jenis' => 'Larangan Truck Masuk', 'kategori' => 'larangan'],
            ['file' => 'Papan Nama 3 Arah.png', 'nama_jenis' => 'Papan Nama 3 Arah', 'kategori' => 'petunjuk'],
            ['file' => 'Papan Nama Jalan.png', 'nama_jenis' => 'Papan Nama Jalan', 'kategori' => 'petunjuk'],
            ['file' => 'Papan ZOSS.png', 'nama_jenis' => 'Papan ZOSS', 'kategori' => 'petunjuk'],
            ['file' => 'Penginapan.png', 'nama_jenis' => 'Penginapan', 'kategori' => 'petunjuk'],
            ['file' => 'Peringatan APILL.png', 'nama_jenis' => 'Peringatan APILL', 'kategori' => 'peringatan'],
            ['file' => 'Peringatan Banyak Tikungan.png', 'nama_jenis' => 'Peringatan Banyak Tikungan', 'kategori' => 'peringatan'],
            ['file' => 'Peringatan Bundaran.png', 'nama_jenis' => 'Peringatan Bundaran', 'kategori' => 'peringatan'],
            ['file' => 'Peringatan Hati-Hati.png', 'nama_jenis' => 'Peringatan Hati-Hati', 'kategori' => 'peringatan'],
            ['file' => 'Peringatan Jembatan.png', 'nama_jenis' => 'Peringatan Jembatan', 'kategori' => 'peringatan'],
            ['file' => 'Peringatan Lalu Lintas Pejalan Kaki Anak.png', 'nama_jenis' => 'Peringatan Lalu Lintas Pejalan Kaki Anak', 'kategori' => 'peringatan'],
            ['file' => 'Peringatan Orang Menyebrang.png', 'nama_jenis' => 'Peringatan Orang Menyebrang', 'kategori' => 'peringatan'],
            ['file' => 'Peringatan Pelebaran Jalan Kedua Sisi.png', 'nama_jenis' => 'Peringatan Pelebaran Jalan Kedua Sisi', 'kategori' => 'peringatan'],
            ['file' => 'Peringatan Penyempitan Jalan Kanan.png', 'nama_jenis' => 'Peringatan Penyempitan Jalan Kanan', 'kategori' => 'peringatan'],
            ['file' => 'Peringatan Penyempitan Jalan Kedua Sisi.png', 'nama_jenis' => 'Peringatan Penyempitan Jalan Kedua Sisi', 'kategori' => 'peringatan'],
            ['file' => 'Peringatan Penyempitan Jalan Kiri.png', 'nama_jenis' => 'Peringatan Penyempitan Jalan Kiri', 'kategori' => 'peringatan'],
            ['file' => 'Peringatan Simpang Empat Kiri Kanan Mayor.png', 'nama_jenis' => 'Peringatan Simpang Empat Kiri Kanan Mayor', 'kategori' => 'peringatan'],
            ['file' => 'Peringatan Simpang Empat Tengah Mayor.png', 'nama_jenis' => 'Peringatan Simpang Empat Tengah Mayor', 'kategori' => 'peringatan'],
            ['file' => 'Peringatan Simpang Empat.png', 'nama_jenis' => 'Peringatan Simpang Empat', 'kategori' => 'peringatan'],
            ['file' => 'Peringatan Simpang Tiga Ganda Kanan Kiri Mayor.png', 'nama_jenis' => 'Peringatan Simpang Tiga Ganda Kanan Kiri Mayor', 'kategori' => 'peringatan'],
            ['file' => 'Peringatan Simpang Tiga Kanan Mayor.png', 'nama_jenis' => 'Peringatan Simpang Tiga Kanan Mayor', 'kategori' => 'peringatan'],
            ['file' => 'Peringatan Simpang Tiga Kanan.png', 'nama_jenis' => 'Peringatan Simpang Tiga Kanan', 'kategori' => 'peringatan'],
            ['file' => 'Peringatan Simpang Tiga Kiri Mayor.png', 'nama_jenis' => 'Peringatan Simpang Tiga Kiri Mayor', 'kategori' => 'peringatan'],
            ['file' => 'Peringatan Simpang Tiga Kiri.png', 'nama_jenis' => 'Peringatan Simpang Tiga Kiri', 'kategori' => 'peringatan'],
            ['file' => 'Peringatan Simpang Tiga Serong Kanan.png', 'nama_jenis' => 'Peringatan Simpang Tiga Serong Kanan', 'kategori' => 'peringatan'],
            ['file' => 'Peringatan Simpang Tiga Serong Kiri.png', 'nama_jenis' => 'Peringatan Simpang Tiga Serong Kiri', 'kategori' => 'peringatan'],
            ['file' => 'Peringatan Simpang Tiga T.png', 'nama_jenis' => 'Peringatan Simpang Tiga T', 'kategori' => 'peringatan'],
            ['file' => 'Peringatan Simpang Tiga Y.png', 'nama_jenis' => 'Peringatan Simpang Tiga Y', 'kategori' => 'peringatan'],
            ['file' => 'Peringatan Tanjakan Landai.png', 'nama_jenis' => 'Peringatan Tanjakan Landai', 'kategori' => 'peringatan'],
            ['file' => 'Peringatan Tikungan Ganda Kiri.png', 'nama_jenis' => 'Peringatan Tikungan Ganda Kiri', 'kategori' => 'peringatan'],
            ['file' => 'Peringatan Tikungan Kanan.png', 'nama_jenis' => 'Peringatan Tikungan Kanan', 'kategori' => 'peringatan'],
            ['file' => 'Peringatan Tikungan Kiri.png', 'nama_jenis' => 'Peringatan Tikungan Kiri', 'kategori' => 'peringatan'],
            ['file' => 'Peringatan Tikungan Tajam Kanan.png', 'nama_jenis' => 'Peringatan Tikungan Tajam Kanan', 'kategori' => 'peringatan'],
            ['file' => 'Peringatan Tikungan Tajam Kiri.png', 'nama_jenis' => 'Peringatan Tikungan Tajam Kiri', 'kategori' => 'peringatan'],
            ['file' => 'Peringatan Turunan.png', 'nama_jenis' => 'Peringatan Turunan', 'kategori' => 'peringatan'],
            ['file' => 'Perintah Berjalan Lurus.png', 'nama_jenis' => 'Perintah Berjalan Lurus', 'kategori' => 'perintah'],
            ['file' => 'Persimpangan Tiga Serong Kanan (Bawah).png', 'nama_jenis' => 'Persimpangan Tiga Serong Kanan (Bawah)', 'kategori' => 'petunjuk'],
            ['file' => 'Petunjuk Gedung Olahraga.png', 'nama_jenis' => 'Petunjuk Gedung Olahraga', 'kategori' => 'petunjuk'],
            ['file' => 'Petunjuk Mesjid.png', 'nama_jenis' => 'Petunjuk Mesjid', 'kategori' => 'petunjuk'],
            ['file' => 'Petunjuk Penyebrangan jalan.png', 'nama_jenis' => 'Petunjuk Penyebrangan Jalan', 'kategori' => 'petunjuk'],
            ['file' => 'Puskesmas.png', 'nama_jenis' => 'Puskesmas', 'kategori' => 'petunjuk'],
            ['file' => 'Putar Balik.png', 'nama_jenis' => 'Putar Balik', 'kategori' => 'petunjuk'],
            ['file' => 'RPPJ.jpg', 'nama_jenis' => 'RPPJ', 'kategori' => 'petunjuk'],
            ['file' => 'Rambu Tempat Makan.png', 'nama_jenis' => 'Rambu Tempat Makan', 'kategori' => 'petunjuk'],
            ['file' => 'Satu Arah.png', 'nama_jenis' => 'Satu Arah', 'kategori' => 'perintah'],
            ['file' => 'SpeedBump.png', 'nama_jenis' => 'Speed Bump', 'kategori' => 'perlengkapan'],
            ['file' => 'Stop.png', 'nama_jenis' => 'Stop', 'kategori' => 'larangan'],
            ['file' => 'Traffic Light.png', 'nama_jenis' => 'Traffic Light', 'kategori' => 'perlengkapan'],
            ['file' => 'Warning Light_.png', 'nama_jenis' => 'Warning Light', 'kategori' => 'perlengkapan'],
            // Despite the near-identical filename to "Larangan Jalan Terus
            // Prioritas" above, this is a genuinely different sign — a
            // red-circle-slash over an up arrow (no going straight), not the
            // yield triangle. Named to match its own filename either way.
            // 'slug' forced explicitly: its filename differs from the other
            // one only by case/parentheses, which Str::slug() strips down to
            // an identical string — without this override the two would
            // silently overwrite each other's image in storage.
            ['file' => 'larangan jalan terus (prioritas).png', 'nama_jenis' => 'Larangan Jalan Terus (Prioritas)', 'kategori' => 'larangan', 'slug' => 'larangan-jalan-terus-prioritas-2'],
        ];

        $sourceDir = __DIR__.'/images/jenis-rambu';

        foreach ($foto as $item) {
            $kategori = self::KATEGORI[$item['kategori']];
            $sourceFile = $sourceDir.'/'.$item['file'];
            $extension = pathinfo($item['file'], PATHINFO_EXTENSION);
            $slug = $item['slug'] ?? Str::slug(pathinfo($item['file'], PATHINFO_FILENAME));
            $path = 'jenis-rambu/'.$slug.'.'.$extension;

            Storage::disk('public')->put($path, file_get_contents($sourceFile));

            JenisRambu::create([
                'nama_jenis' => $item['nama_jenis'],
                'spesifikasi_standar' => $kategori['deskripsi'],
                'bentuk_ikon' => $item['bentuk_ikon'] ?? $kategori['bentuk_ikon'],
                'gambar_referensi' => $path,
            ]);
        }
    }
}
