<?php

namespace Database\Seeders;

use App\Models\AuditLog;
use App\Models\BarangBahan;
use App\Models\DikerjakanOleh;
use App\Models\JenisRambu;
use App\Models\Kendala;
use App\Models\LaporanKondisi;
use App\Models\LaporanPengerjaan;
use App\Models\Notifikasi;
use App\Models\Rambu;
use App\Models\RambuPasang;
use App\Models\Spk;
use App\Models\User;
use Illuminate\Database\Seeder;

class MarlinDemoSeeder extends Seeder
{
    /** @var array<int, User> */
    private array $petugas = [];

    private User $admin;

    /** @var array<string, array{lat: array{float, float}, lng: array{float, float}, jalan: array<int, string>, kelurahan: array<int, string>}> */
    private array $wilayahBanjarmasin = [
        'Banjarmasin Utara' => [
            'lat' => [-3.300, -3.290],
            'lng' => [114.585, 114.610],
            'jalan' => ['Brigjen H. Hasan Basri', 'Sutoyo S', 'Kayu Tangi', 'HKSN', 'Simpang Ulin'],
            'kelurahan' => ['Alalak Utara', 'Sungai Miai', 'Surgi Mufti', 'Pangeran', 'Antasan Kecil Timur'],
        ],
        'Banjarmasin Selatan' => [
            'lat' => [-3.350, -3.335],
            'lng' => [114.585, 114.610],
            'jalan' => ['Pramuka', 'Handil Bakti', 'Bumi Mas Raya', 'Tatah Belayung', 'Kelayan A'],
            'kelurahan' => ['Kelayan Timur', 'Kelayan Selatan', 'Pemurus Baru', 'Murung Raya', 'Tanjung Pagar'],
        ],
        'Banjarmasin Timur' => [
            'lat' => [-3.320, -3.310],
            'lng' => [114.615, 114.640],
            'jalan' => ['Sultan Adam', 'Cempaka', 'Pekapuran Raya', 'Karang Mekar', 'Manggis'],
            'kelurahan' => ['Pekapuran Raya', 'Karang Mekar', 'Sungai Bilu', 'Kuripan', 'Benua Anyar'],
        ],
        'Banjarmasin Barat' => [
            'lat' => [-3.330, -3.318],
            'lng' => [114.560, 114.585],
            'jalan' => ['Belitung Darat', 'Teluk Tiram', 'Basirih', 'Kuin Utara', 'Barito Hilir'],
            'kelurahan' => ['Basirih', 'Kuin Cerucuk', 'Telawang', 'Teluk Tiram', 'Pelambuan'],
        ],
        'Banjarmasin Tengah' => [
            'lat' => [-3.325, -3.315],
            'lng' => [114.585, 114.600],
            'jalan' => ['Ahmad Yani', 'Lambung Mangkurat', 'Pangeran Antasari', 'Veteran', 'Pasar Lama'],
            'kelurahan' => ['Antasan Besar', 'Kertak Baru Ulu', 'Mawar', 'Pasar Lama', 'Teluk Dalam'],
        ],
    ];

    private array $lokasiDetail = [
        'perempatan lampu merah', 'simpang tiga', 'depan pasar', 'samping masjid', 'depan sekolah',
        'tikungan tajam', 'jembatan penyeberangan', 'depan kantor kelurahan', 'putaran balik', 'gerbang komplek',
    ];

    public function run(): void
    {
        $this->admin = User::where('nip', '198501012010011001')->firstOrFail();

        $this->seedPetugas();
        $this->seedSpkAktifBeragamStatus();
        $this->seedSpkSelesai();
        $this->seedTemuanLapangan();
        $this->seedNotifikasiTambahan();
    }

    private function seedPetugas(): void
    {
        $data = [
            ['name' => 'Budi Santoso', 'nip' => '199203102016011001', 'bidang' => 'Bidang Lalu Lintas', 'jabatan' => 'Kepala Regu', 'jenis_kelamin' => 'L'],
            ['name' => 'Ahmad Fauzi', 'nip' => '199505202018011002', 'bidang' => 'Bidang Lalu Lintas', 'jabatan' => 'Anggota Workshop', 'jenis_kelamin' => 'L'],
            ['name' => 'Siti Aminah', 'nip' => '199407152017022001', 'bidang' => 'Bidang Keselamatan', 'jabatan' => 'Staf Lapangan', 'jenis_kelamin' => 'P'],
            ['name' => 'Rizky Ramadhan', 'nip' => '199611052019011003', 'bidang' => 'Bidang Sarana dan Prasarana', 'jabatan' => 'Teknisi', 'jenis_kelamin' => 'L'],
            ['name' => 'Dewi Lestari', 'nip' => '199308182016022002', 'bidang' => 'Bidang Angkutan', 'jabatan' => 'Staf Lapangan', 'jenis_kelamin' => 'P'],
            ['name' => 'Muhammad Iqbal', 'nip' => '199712302020011004', 'bidang' => 'Bidang Lalu Lintas', 'jabatan' => 'Anggota Workshop', 'jenis_kelamin' => 'L'],
        ];

        foreach ($data as $row) {
            $this->petugas[] = User::factory()->create([
                'name' => $row['name'],
                'nama_panggilan' => explode(' ', $row['name'])[0],
                'nip' => $row['nip'],
                'username' => strtolower(str_replace(' ', '.', $row['name'])),
                'jenis_kelamin' => $row['jenis_kelamin'],
                'bidang' => $row['bidang'],
                'jabatan' => $row['jabatan'],
            ]);
        }
    }

    private function randomKoordinat(string $wilayah): string
    {
        $bounds = $this->wilayahBanjarmasin[$wilayah];
        $lat = $bounds['lat'][0] + (mt_rand() / mt_getrandmax()) * ($bounds['lat'][1] - $bounds['lat'][0]);
        $lng = $bounds['lng'][0] + (mt_rand() / mt_getrandmax()) * ($bounds['lng'][1] - $bounds['lng'][0]);

        return round($lat, 6).','.round($lng, 6);
    }

    private function randomLokasi(): string
    {
        return $this->lokasiDetail[array_rand($this->lokasiDetail)];
    }

    private function randomJalan(string $wilayah): string
    {
        $bounds = $this->wilayahBanjarmasin[$wilayah];

        return $bounds['jalan'][array_rand($bounds['jalan'])];
    }

    private function randomRt(): string
    {
        return (string) mt_rand(1, 40);
    }

    private function randomKelurahan(string $wilayah): string
    {
        $bounds = $this->wilayahBanjarmasin[$wilayah];

        return $bounds['kelurahan'][array_rand($bounds['kelurahan'])];
    }

    private function buatRambu(string $wilayah, JenisRambu $jenis, string $kondisi = 'baik', bool $sudahTerpasang = true, ?string $jalan = null, ?string $rt = null): Rambu
    {
        return Rambu::create([
            'jenis_rambu_id' => $jenis->id,
            'jalan' => $jalan ?? $this->randomJalan($wilayah),
            'rt' => $rt ?? $this->randomRt(),
            'lokasi' => $this->randomLokasi(),
            'koordinat' => $this->randomKoordinat($wilayah),
            'kondisi_terkini' => $kondisi,
            'sudah_terpasang' => $sudahTerpasang,
        ]);
    }

    private function nomorSurat(int $urutan): string
    {
        return sprintf('SR-%d/BJM/%04d', now()->year, $urutan);
    }

    private function seedSpkAktifBeragamStatus(): void
    {
        $jenisRambuList = JenisRambu::all();
        $wilayahList = array_keys($this->wilayahBanjarmasin);

        // SPK #1: pasang baru, aktif, prioritas, belum ada rambu_pasang yang dikerjakan.
        $wilayah = $wilayahList[0];
        $jalan = $this->randomJalan($wilayah);
        $rt = $this->randomRt();
        $spk1 = Spk::create([
            'nomor_surat' => $this->nomorSurat(1),
            'dibuat_oleh' => $this->admin->id,
            'jenis_spk' => 'pasang_baru',
            'jalan' => $jalan,
            'rt' => $rt,
            'kelurahan' => $this->randomKelurahan($wilayah),
            'deadline' => now()->addDays(10),
            'prioritas' => true,
            'urgensi' => 'tinggi',
            'status' => 'aktif',
            'asal_permintaan' => 'laporan_masyarakat',
            'keterangan_asal' => 'Warga RT 12 melalui kantor kelurahan',
            'catatan_pekerja_tambahan' => 'Bawa tangga lipat, area padat kendaraan.',
        ]);

        foreach (range(1, 3) as $i) {
            $rambu = $this->buatRambu($wilayah, $jenisRambuList->random(), 'baik', false, $jalan, $rt);
            RambuPasang::create([
                'rambu_spk_id' => $spk1->id,
                'rambu_id' => $rambu->id,
                'jenis_pekerjaan' => 'pasang_baru',
                'jumlah' => 1,
                'catatan_instruksi' => 'Pasang menghadap arus lalu lintas utama.',
                'status' => 'belum',
            ]);
        }

        AuditLog::create([
            'user_id' => $this->admin->id,
            'spk_id' => $spk1->id,
            'aksi' => 'spk_dibuat',
            'keterangan' => "SPK {$spk1->nomor_surat} dibuat untuk wilayah {$spk1->wilayah}.",
        ]);

        DikerjakanOleh::create(['by_spk_id' => $spk1->id, 'by_user_id' => $this->petugas[0]->id, 'is_perwakilan' => true]);
        DikerjakanOleh::create(['by_spk_id' => $spk1->id, 'by_user_id' => $this->petugas[1]->id, 'is_perwakilan' => false]);

        // SPK #2: perbaikan, aktif, satu rambu_pasang menunggu validasi, satu tertunda (kendala).
        $wilayah = $wilayahList[1];
        $jalan = $this->randomJalan($wilayah);
        $rt = $this->randomRt();
        $spk2 = Spk::create([
            'nomor_surat' => $this->nomorSurat(2),
            'dibuat_oleh' => $this->admin->id,
            'jenis_spk' => 'perbaikan',
            'jalan' => $jalan,
            'rt' => $rt,
            'kelurahan' => $this->randomKelurahan($wilayah),
            'deadline' => now()->addDays(4),
            'prioritas' => false,
            'urgensi' => 'sedang',
            'status' => 'aktif',
            'asal_permintaan' => 'evaluasi_petugas',
            'catatan_pekerja_tambahan' => 'Cek ulang baut penyangga sebelum pemasangan.',
        ]);

        $rambuRusak1 = $this->buatRambu($wilayah, $jenisRambuList->random(), 'rusak', true, $jalan, $rt);
        $rp2a = RambuPasang::create([
            'rambu_spk_id' => $spk2->id,
            'rambu_id' => $rambuRusak1->id,
            'jenis_pekerjaan' => 'perbaikan',
            'jumlah' => 1,
            'catatan_instruksi' => 'Tiang miring akibat tertabrak.',
            'status' => 'menunggu_validasi',
        ]);

        $laporanPending = LaporanPengerjaan::create([
            'rambu_pasang_id' => $rp2a->id,
            'dilaporkan_oleh' => $this->petugas[2]->id,
            'koordinat_gps' => $rambuRusak1->koordinat,
            'catatan_lapangan' => 'Sudah diluruskan dan dikencangkan kembali.',
            'status' => 'diajukan',
        ]);

        BarangBahan::create(['laporan_pengerjaan_id' => $laporanPending->id, 'nama' => 'Baut penyangga', 'jumlah' => 4, 'satuan' => 'buah']);
        BarangBahan::create(['laporan_pengerjaan_id' => $laporanPending->id, 'nama' => 'Semen instan', 'jumlah' => 1, 'satuan' => 'sak']);

        $rambuRusak2 = $this->buatRambu($wilayah, $jenisRambuList->random(), 'rusak', true, $jalan, $rt);
        $rp2b = RambuPasang::create([
            'rambu_spk_id' => $spk2->id,
            'rambu_id' => $rambuRusak2->id,
            'jenis_pekerjaan' => 'perbaikan',
            'jumlah' => 1,
            'status' => 'tertunda',
        ]);

        Kendala::create([
            'rambu_pasang_id' => $rp2b->id,
            'dilaporkan_oleh' => $this->petugas[2]->id,
            'alasan' => 'Lokasi sedang ada perbaikan drainase dari dinas lain, akses tertutup sementara.',
        ]);

        AuditLog::create(['user_id' => $this->admin->id, 'spk_id' => $spk2->id, 'aksi' => 'spk_dibuat', 'keterangan' => "SPK {$spk2->nomor_surat} dibuat untuk wilayah {$spk2->wilayah}."]);
        AuditLog::create(['user_id' => $this->petugas[2]->id, 'spk_id' => $spk2->id, 'aksi' => 'laporan_dikirim', 'keterangan' => "Laporan pengerjaan dikirim untuk rambu di {$rambuRusak1->wilayah}, {$rambuRusak1->lokasi}."]);
        AuditLog::create(['user_id' => $this->petugas[2]->id, 'spk_id' => $spk2->id, 'aksi' => 'kendala_diajukan', 'keterangan' => "Kendala diajukan untuk rambu di {$rambuRusak2->wilayah}, {$rambuRusak2->lokasi}."]);

        DikerjakanOleh::create(['by_spk_id' => $spk2->id, 'by_user_id' => $this->petugas[2]->id, 'is_perwakilan' => true]);

        Notifikasi::create(['user_id' => $this->admin->id, 'judul' => 'Laporan Pengerjaan Masuk', 'pesan' => "Petugas mengirim laporan pengerjaan untuk SPK {$spk2->nomor_surat}.", 'dibaca' => false]);
        Notifikasi::create(['user_id' => $this->admin->id, 'judul' => 'Kendala Diajukan', 'pesan' => "Petugas melaporkan kendala pada SPK {$spk2->nomor_surat}.", 'dibaca' => false]);

        // SPK #3: pasang baru, belum ada yang join sama sekali, deadline dekat (mendekati deadline).
        $wilayah = $wilayahList[2];
        $jalan = $this->randomJalan($wilayah);
        $rt = $this->randomRt();
        $spk3 = Spk::create([
            'nomor_surat' => $this->nomorSurat(3),
            'dibuat_oleh' => $this->admin->id,
            'jenis_spk' => 'pasang_baru',
            'jalan' => $jalan,
            'rt' => $rt,
            'kelurahan' => $this->randomKelurahan($wilayah),
            'deadline' => now()->addDays(2),
            'urgensi' => 'tinggi',
            'status' => 'aktif',
            'asal_permintaan' => 'instruksi_pemerintah',
            'keterangan_asal' => 'Instruksi Dinas Perhubungan Provinsi',
        ]);

        foreach (range(1, 2) as $i) {
            $rambu = $this->buatRambu($wilayah, $jenisRambuList->random(), 'baik', false, $jalan, $rt);
            RambuPasang::create([
                'rambu_spk_id' => $spk3->id,
                'rambu_id' => $rambu->id,
                'jenis_pekerjaan' => 'pasang_baru',
                'jumlah' => 2,
                'status' => 'belum',
            ]);
        }

        AuditLog::create(['user_id' => $this->admin->id, 'spk_id' => $spk3->id, 'aksi' => 'spk_dibuat', 'keterangan' => "SPK {$spk3->nomor_surat} dibuat untuk wilayah {$spk3->wilayah}."]);

        foreach ($this->petugas as $p) {
            Notifikasi::create(['user_id' => $p->id, 'judul' => 'SPK Baru Tersedia', 'pesan' => "SPK {$spk3->nomor_surat} untuk wilayah {$spk3->wilayah} sudah bisa dikerjakan.", 'dibaca' => false]);
        }
    }

    private function seedSpkSelesai(): void
    {
        $jenisRambuList = JenisRambu::all();
        $wilayahList = array_keys($this->wilayahBanjarmasin);

        // SPK #4: pasang baru, sudah selesai sepenuhnya (divalidasi diterima).
        $wilayah = $wilayahList[3];
        $jalan = $this->randomJalan($wilayah);
        $rt = $this->randomRt();
        $spk4 = Spk::create([
            'nomor_surat' => $this->nomorSurat(4),
            'dibuat_oleh' => $this->admin->id,
            'jenis_spk' => 'pasang_baru',
            'jalan' => $jalan,
            'rt' => $rt,
            'kelurahan' => $this->randomKelurahan($wilayah),
            'deadline' => now()->subDays(5),
            'urgensi' => 'rendah',
            'status' => 'selesai',
            'asal_permintaan' => 'internal',
            'created_at' => now()->subDays(20),
        ]);

        $rambu4 = $this->buatRambu($wilayah, $jenisRambuList->random(), 'baik', true, $jalan, $rt);
        $rp4 = RambuPasang::create([
            'rambu_spk_id' => $spk4->id,
            'rambu_id' => $rambu4->id,
            'jenis_pekerjaan' => 'pasang_baru',
            'jumlah' => 1,
            'status' => 'selesai',
        ]);

        $laporan4 = LaporanPengerjaan::create([
            'rambu_pasang_id' => $rp4->id,
            'dilaporkan_oleh' => $this->petugas[3]->id,
            'koordinat_gps' => $rambu4->koordinat,
            'catatan_lapangan' => 'Pemasangan selesai sesuai instruksi.',
            'status' => 'diterima',
            'divalidasi_oleh' => $this->admin->id,
            'divalidasi_pada' => now()->subDays(15),
        ]);

        BarangBahan::create(['laporan_pengerjaan_id' => $laporan4->id, 'nama' => 'Tiang rambu galvanis', 'jumlah' => 1, 'satuan' => 'batang']);
        BarangBahan::create(['laporan_pengerjaan_id' => $laporan4->id, 'nama' => 'Plat rambu', 'jumlah' => 1, 'satuan' => 'buah']);

        DikerjakanOleh::create(['by_spk_id' => $spk4->id, 'by_user_id' => $this->petugas[3]->id, 'is_perwakilan' => true]);

        AuditLog::create(['user_id' => $this->admin->id, 'spk_id' => $spk4->id, 'aksi' => 'spk_dibuat', 'keterangan' => "SPK {$spk4->nomor_surat} dibuat untuk wilayah {$spk4->wilayah}.", 'created_at' => now()->subDays(20)]);
        AuditLog::create(['user_id' => $this->petugas[3]->id, 'spk_id' => $spk4->id, 'aksi' => 'laporan_dikirim', 'keterangan' => "Laporan pengerjaan dikirim untuk rambu di {$rambu4->wilayah}, {$rambu4->lokasi}.", 'created_at' => now()->subDays(16)]);
        AuditLog::create(['user_id' => $this->admin->id, 'spk_id' => $spk4->id, 'aksi' => 'validasi_diterima', 'keterangan' => "Laporan pengerjaan untuk {$rambu4->wilayah}, {$rambu4->lokasi} diterima.", 'created_at' => now()->subDays(15)]);

        Notifikasi::create(['user_id' => $this->petugas[3]->id, 'judul' => 'Laporan Diterima', 'pesan' => "Laporan pengerjaan Anda untuk SPK {$spk4->nomor_surat} telah diterima.", 'dibaca' => true]);

        // SPK #5: perbaikan yang pernah direvisi lalu selesai (riwayat 2 laporan_pengerjaan pada rambu_pasang yang sama).
        $wilayah = $wilayahList[4];
        $jalan = $this->randomJalan($wilayah);
        $rt = $this->randomRt();
        $spk5 = Spk::create([
            'nomor_surat' => $this->nomorSurat(5),
            'dibuat_oleh' => $this->admin->id,
            'jenis_spk' => 'perbaikan',
            'jalan' => $jalan,
            'rt' => $rt,
            'kelurahan' => $this->randomKelurahan($wilayah),
            'deadline' => now()->subDays(2),
            'urgensi' => 'sedang',
            'status' => 'selesai',
            'asal_permintaan' => 'laporan_masyarakat',
            'keterangan_asal' => 'Pak RT setempat',
            'created_at' => now()->subDays(12),
        ]);

        $rambu5 = $this->buatRambu($wilayah, $jenisRambuList->random(), 'baik', true, $jalan, $rt);
        $rp5 = RambuPasang::create([
            'rambu_spk_id' => $spk5->id,
            'rambu_id' => $rambu5->id,
            'jenis_pekerjaan' => 'perbaikan',
            'jumlah' => 1,
            'status' => 'selesai',
        ]);

        LaporanPengerjaan::create([
            'rambu_pasang_id' => $rp5->id,
            'dilaporkan_oleh' => $this->petugas[4]->id,
            'catatan_lapangan' => 'Sudah dicat ulang.',
            'status' => 'ditolak',
            'catatan_penolakan' => 'Warna cat belum sesuai standar reflektif, mohon diulang.',
            'divalidasi_oleh' => $this->admin->id,
            'divalidasi_pada' => now()->subDays(8),
            'created_at' => now()->subDays(9),
        ]);

        $laporan5b = LaporanPengerjaan::create([
            'rambu_pasang_id' => $rp5->id,
            'dilaporkan_oleh' => $this->petugas[4]->id,
            'koordinat_gps' => $rambu5->koordinat,
            'catatan_lapangan' => 'Sudah dicat ulang memakai cat reflektif standar.',
            'status' => 'diterima',
            'divalidasi_oleh' => $this->admin->id,
            'divalidasi_pada' => now()->subDays(3),
            'created_at' => now()->subDays(5),
        ]);

        BarangBahan::create(['laporan_pengerjaan_id' => $laporan5b->id, 'nama' => 'Cat reflektif', 'jumlah' => 2, 'satuan' => 'kaleng']);

        DikerjakanOleh::create(['by_spk_id' => $spk5->id, 'by_user_id' => $this->petugas[4]->id, 'is_perwakilan' => true]);

        AuditLog::create(['user_id' => $this->admin->id, 'spk_id' => $spk5->id, 'aksi' => 'validasi_ditolak', 'keterangan' => "Laporan pengerjaan untuk {$rambu5->wilayah}, {$rambu5->lokasi} ditolak: Warna cat belum sesuai standar reflektif, mohon diulang.", 'created_at' => now()->subDays(8)]);
        AuditLog::create(['user_id' => $this->admin->id, 'spk_id' => $spk5->id, 'aksi' => 'validasi_diterima', 'keterangan' => "Laporan pengerjaan untuk {$rambu5->wilayah}, {$rambu5->lokasi} diterima.", 'created_at' => now()->subDays(3)]);

        Notifikasi::create(['user_id' => $this->petugas[4]->id, 'judul' => 'Laporan Ditolak', 'pesan' => "Laporan pengerjaan Anda untuk SPK {$spk5->nomor_surat} ditolak: Warna cat belum sesuai standar reflektif, mohon diulang.", 'dibaca' => true]);
        Notifikasi::create(['user_id' => $this->petugas[4]->id, 'judul' => 'Laporan Diterima', 'pesan' => "Laporan pengerjaan Anda untuk SPK {$spk5->nomor_surat} telah diterima.", 'dibaca' => false]);

        // SPK #6: dibatalkan.
        $wilayah = $wilayahList[0];
        $jalan = $this->randomJalan($wilayah);
        $rt = $this->randomRt();
        $spk6 = Spk::create([
            'nomor_surat' => $this->nomorSurat(6),
            'dibuat_oleh' => $this->admin->id,
            'jenis_spk' => 'pasang_baru',
            'jalan' => $jalan,
            'rt' => $rt,
            'kelurahan' => $this->randomKelurahan($wilayah),
            'deadline' => now()->subDays(1),
            'urgensi' => 'rendah',
            'status' => 'dibatalkan',
            'asal_permintaan' => 'internal',
            'created_at' => now()->subDays(18),
        ]);

        $rambu6 = $this->buatRambu($wilayah, $jenisRambuList->random(), 'baik', false, $jalan, $rt);
        RambuPasang::create([
            'rambu_spk_id' => $spk6->id,
            'rambu_id' => $rambu6->id,
            'jenis_pekerjaan' => 'pasang_baru',
            'jumlah' => 1,
            'status' => 'batal',
        ]);

        AuditLog::create(['user_id' => $this->admin->id, 'spk_id' => $spk6->id, 'aksi' => 'spk_dibuat', 'keterangan' => "SPK {$spk6->nomor_surat} dibuat untuk wilayah {$spk6->wilayah}.", 'created_at' => now()->subDays(18)]);
        AuditLog::create(['user_id' => $this->admin->id, 'spk_id' => $spk6->id, 'aksi' => 'spk_dibatalkan', 'keterangan' => "SPK {$spk6->nomor_surat} dibatalkan karena anggaran dialihkan.", 'created_at' => now()->subDays(10)]);
    }

    private function seedTemuanLapangan(): void
    {
        $jenisRambuList = JenisRambu::all();
        $wilayahList = array_keys($this->wilayahBanjarmasin);

        $rambuTemuan1 = $this->buatRambu($wilayahList[1], $jenisRambuList->random(), 'rusak', true);
        LaporanKondisi::create([
            'rambu_id' => $rambuTemuan1->id,
            'dilaporkan_oleh' => $this->petugas[0]->id,
            'kondisi_dilaporkan' => 'rusak',
            'catatan' => 'Tiang bengkok akibat tertabrak kendaraan saat patroli malam.',
            'status_tindak_lanjut' => 'baru',
        ]);
        AuditLog::create(['user_id' => $this->petugas[0]->id, 'aksi' => 'laporan_kondisi_dibuat', 'keterangan' => "Temuan kondisi (rusak) dilaporkan untuk rambu di {$rambuTemuan1->wilayah}, {$rambuTemuan1->lokasi}."]);
        Notifikasi::create(['user_id' => $this->admin->id, 'judul' => 'Temuan Kondisi Rambu', 'pesan' => "Petugas melaporkan rambu di {$rambuTemuan1->wilayah}, {$rambuTemuan1->lokasi} berkondisi rusak.", 'dibaca' => false]);

        $rambuTemuan2 = $this->buatRambu($wilayahList[2], $jenisRambuList->random(), 'rusak', true);
        LaporanKondisi::create([
            'rambu_id' => $rambuTemuan2->id,
            'dilaporkan_oleh' => $this->petugas[1]->id,
            'kondisi_dilaporkan' => 'rusak',
            'catatan' => 'Plat rambu pudar, sudah tidak terbaca jelas di malam hari.',
            'status_tindak_lanjut' => 'sudah_dibuatkan_spk',
        ]);

        $rambuTemuan3 = $this->buatRambu($wilayahList[3], $jenisRambuList->random(), 'baik', true);
        LaporanKondisi::create([
            'rambu_id' => $rambuTemuan3->id,
            'dilaporkan_oleh' => $this->petugas[5]->id,
            'kondisi_dilaporkan' => 'rusak',
            'catatan' => 'Laporan warga ternyata keliru, rambu masih dalam kondisi baik.',
            'status_tindak_lanjut' => 'ditolak',
        ]);
    }

    private function seedNotifikasiTambahan(): void
    {
        Notifikasi::create(['user_id' => $this->admin->id, 'judul' => 'Selamat Datang di MARLIN', 'pesan' => 'Sistem Manajemen Rambu Lalu Lintas siap digunakan.', 'dibaca' => true]);

        foreach ($this->petugas as $p) {
            Notifikasi::create(['user_id' => $p->id, 'judul' => 'Selamat Datang di MARLIN', 'pesan' => 'Akun Anda sudah aktif, silakan cek Daftar Surat Aktif untuk mulai bertugas.', 'dibaca' => true]);
        }
    }
}
