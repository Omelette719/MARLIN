# Sistem MARLIN: Manajemen Rambu Lalu Lintas

Aplikasi web internal untuk **Dinas Perhubungan Kota Banjarmasin** yang mengelola seluruh siklus hidup rambu lalu lintas: dari permintaan pemasangan/perbaikan, penerbitan Surat Perintah Kerja (SPK), pengerjaan di lapangan oleh petugas, validasi hasil kerja oleh admin, sampai pemantauan lokasi rambu lewat peta interaktif.

Dibangun dengan **Laravel 13**, **Livewire 4** (class-based components), **Flux UI 2** (free tier), **SQLite**, **Leaflet** (peta), dan **DomPDF** (surat pengantar & laporan bulanan dalam PDF).

> 📚 Dokumen ini cuma ringkasan. Dokumentasi lengkap per topik (fitur detail, penjelasan tiap halaman, alur bisnis, skema database, keamanan) ada di folder **[`docs/`](docs/README.md)**.

---

## Daftar Isi

- [Latar Belakang](#latar-belakang)
- [Peran Pengguna](#peran-pengguna)
- [Alur Kerja SPK](#alur-kerja-spk)
- [Fitur](#fitur)
  - [Fitur Admin](#fitur-admin)
  - [Fitur Petugas Lapangan](#fitur-petugas-lapangan)
  - [Fitur Bersama](#fitur-bersama)
- [Struktur Database](#struktur-database)
- [Tumpukan Teknologi](#tumpukan-teknologi)
- [Cara Menjalankan (Setelah Clone)](#cara-menjalankan-setelah-clone)
- [Akun Setelah Seeding](#akun-setelah-seeding)
- [Menjalankan Test](#menjalankan-test)
- [Struktur Direktori Penting](#struktur-direktori-penting)
- [CI/CD](#cicd)
- [Dokumentasi Lengkap](#dokumentasi-lengkap)

---

## Latar Belakang

Sebelumnya, proses pengelolaan rambu lalu lintas di Dishub Banjarmasin dilakukan manual: surat pengantar diketik ulang tiap kali, tidak ada pencatatan riwayat kondisi rambu yang terpusat, dan sulit memantau rambu mana yang sudah dipasang, rusak, atau sedang dalam proses perbaikan.

MARLIN (Manajemen Rambu Lalu Lintas) dibangun untuk menjawab itu: satu sistem yang menjadi sumber kebenaran tunggal untuk:
- Daftar seluruh rambu beserta lokasi (koordinat), kondisi, dan riwayat fotonya.
- Siklus SPK (Surat Perintah Kerja) dari dibuat sampai selesai/dibatalkan, termasuk validasi hasil kerja.
- Laporan kendala lapangan dan temuan kondisi rusak yang independen dari SPK aktif.
- Peta interaktif seluruh rambu dengan status berwarna secara real-time.
- Audit log: siapa melakukan apa, kapan, ke SPK mana.
- Laporan bulanan (PDF) untuk dilaporkan ke atasan.

## Peran Pengguna

Sistem punya 2 peran (`role` di tabel `users`):

| Role | Deskripsi | Contoh Hak |
|---|---|---|
| `admin` | Staf Dishub yang membuat SPK, memvalidasi hasil kerja, mengelola master data & akun | Buat/lihat semua SPK, validasi laporan pengerjaan, kelola user, lihat semua audit log |
| `user` (petugas lapangan) | Tim yang mengerjakan pemasangan/perbaikan rambu di lapangan | Gabung SPK, kirim laporan pengerjaan (foto sebelum/sesudah, GPS), lapor kendala, lapor temuan kondisi rusak |

Setiap route dijaga middleware `EnsureUserHasRole:admin` atau `EnsureUserHasRole:user` (lihat `app/Http/Middleware/EnsureUserHasRole.php`). Mengakses route peran lain otomatis di-redirect ke dashboard sesuai peran, bukan error.

Login menggunakan **NIP + kata sandi** (bukan email), dikonfigurasi lewat `config/fortify.php` (`'username' => 'nip'`). Tersedia juga Two-Factor Authentication (2FA) opsional per akun.

## Alur Kerja SPK

```
Admin buat SPK (pasang baru / perbaikan)
        │
        ▼
Petugas gabung SPK → kerjakan di lapangan
        │
        ├─ Ada kendala? → isi Form Kendala → status rambu jadi "tertunda"
        │
        ▼ Tidak ada kendala
Isi Form Laporan Pengerjaan (foto sebelum/sesudah, GPS, barang/bahan)
        │
        ▼
Admin buka Halaman Validasi
        │
        ├─ Ditolak → isi catatan penolakan → status kembali "revisi", petugas ulangi
        │
        ▼ Diterima
Status rambu_pasang → "selesai"
        │
        ▼ (semua rambu di SPK selesai)
Petugas ajukan Laporan Akhir → status SPK → "selesai"
```

Urgensi SPK (`Rendah` / `Sedang` / `Tinggi`) dihitung otomatis berdasarkan sisa hari ke deadline (≤2 hari = Tinggi, ≤7 hari = Sedang, selebihnya Rendah), atau langsung Tinggi kalau ditandai **Prioritas**.

Rambu yang ditemukan rusak lewat **Lapor Temuan Kondisi** (independen dari SPK aktif) masuk ke antrean "belum ditindaklanjuti" di admin, dan bisa langsung dijadikan dasar pembuatan SPK perbaikan baru.

## Fitur

### Fitur Admin

- **Dashboard**: ringkasan jumlah SPK aktif, rambu rusak, laporan menunggu validasi, dll.
- **Buat Surat (SPK)**: form pasang-baru vs perbaikan, banyak baris rambu sekaligus, deteksi rambu yang sudah terdaftar (searchable-select) vs rambu baru, alamat terstruktur (Jalan/RT/Kelurahan + Lokasi spesifik), tanggal survei opsional, data RT/Perwakilan, upload file referensi.
- **Daftar & Detail Surat**: pencarian, filter status/jenis, kartu ringkas per SPK.
- **Validasi Pengerjaan**: terima/tolak laporan pengerjaan per rambu, dengan catatan penolakan yang mengembalikan status ke petugas untuk direvisi.
- **Laporan Bulanan**: rekap rambu terpasang/belum terpasang & SPK selesai/belum dalam rentang bulan tertentu, bisa diexport ke PDF (`Support/LaporanBulanan.php` + `admin/laporan/export`).
- **Surat Pengantar (PDF)**: dibuat on-the-fly (tidak disimpan sebagai file statis) dari data SPK + rambu + RT Perwakilan + tim pengerjaan terkini, jadi selalu sinkron dengan data terbaru.
- **Kelola Jenis Rambu**: master data kategori rambu (nama, spesifikasi, gambar referensi, bentuk ikon peta).
- **Kelola Rambu**: daftar semua rambu terpasang, filter berdasar kondisi/wilayah.
- **Kelola Pengguna**: tambah/edit akun, aktifkan/nonaktifkan petugas (bukan hapus permanen).
- **Temuan Kondisi Belum Ditindaklanjuti**: antrean laporan kerusakan dari petugas yang belum dibuatkan SPK.
- **Audit Log**: riwayat aksi penting (SPK dibuat, laporan dikirim, validasi diterima/ditolak, dll).
- **System Error Log**: exception tak terduga di aplikasi otomatis tercatat di sini (lihat `bootstrap/app.php`), memudahkan debugging tanpa perlu akses server.

### Fitur Petugas Lapangan

- **Dashboard**: ringkasan tugas aktif milik tim.
- **SPK Sedang Dikerjakan**: daftar SPK yang timnya sudah terdaftar untuk mengerjakan.
- **Detail SPK**: lihat instruksi, gabung sebagai anggota/perwakilan tim, unduh Surat Pengantar.
- **Form Laporan Pengerjaan**: foto sebelum/sesudah (wajib), koordinat GPS, catatan lapangan, daftar barang/bahan yang dipakai.
- **Form Kendala**: laporkan kalau pemasangan tidak bisa dilanjutkan (foto wajib), rambu otomatis berstatus "tertunda".
- **Lapor Temuan Kondisi**: laporkan rambu yang ditemukan rusak di lapangan, terlepas dari SPK aktif.
- **Riwayat Pekerjaan Saya**: semua SPK yang pernah dikerjakan tim, bisa difilter per bulan (bukti kerja).

### Fitur Bersama

- **Peta Interaktif** (Leaflet + OpenStreetMap): semua rambu tampil sebagai pin berwarna sesuai status (merah=urgent/prioritas, kuning=rusak/perbaikan berjalan, cyan=menunggu validasi, biru tua=selesai/kondisi baik, abu=belum dikerjakan), dengan kartu info saat hover (auto-flip kiri/kanan biar selalu kelihatan).
- **Detail Rambu**: riwayat foto, kondisi, dan SPK yang pernah menyentuh rambu tersebut.
- **Notifikasi**: pemberitahuan in-app (SPK baru tersedia, laporan diterima/ditolak, dll).
- **Settings**: profil, keamanan (ganti password, 2FA).

## Struktur Database

13 tabel domain (di luar tabel bawaan Laravel seperti `users`, `sessions`, `cache`, `jobs`):

| Tabel | Fungsi | Relasi Penting |
|---|---|---|
| `jenis_rambu` | Master kategori rambu (nama, spesifikasi, gambar, bentuk ikon peta) | `hasMany` → `rambu` |
| `rambu` | Satu rambu fisik: jenis, alamat (`wilayah` komposit + `jalan`/`rt`/`kelurahan` terstruktur), `lokasi` spesifik, koordinat, kondisi, status terpasang | `belongsTo` → `jenis_rambu`; `hasMany` → `rambu_pasang`, `laporan_kondisi` |
| `spk` | Surat Perintah Kerja: nomor surat, alamat, deadline, urgensi, status, jenis (pasang baru/perbaikan), asal permintaan, perihal | `hasMany` → `rambu_pasang`, `dikerjakan_oleh`, `rt_perwakilan`, `audit_log` |
| `rambu_pasang` | Baris pekerjaan per rambu dalam satu SPK (jumlah, foto survei, status pengerjaan) | `belongsTo` → `spk`, `rambu`, `laporan_kondisi`; `hasMany` → `laporan_pengerjaan`, `kendala` |
| `dikerjakan_oleh` | Pivot petugas ↔ SPK (siapa gabung, apakah sebagai perwakilan) | `belongsTo` → `spk`, `users` |
| `laporan_pengerjaan` | Laporan hasil kerja petugas per `rambu_pasang` (foto sesudah, GPS, status diajukan/diterima/ditolak, validator) | `belongsTo` → `rambu_pasang`, `users`; `hasMany` → `barang_bahan` |
| `barang_bahan` | Daftar barang/bahan yang dipakai dalam satu laporan pengerjaan | `belongsTo` → `laporan_pengerjaan` |
| `kendala` | Laporan kendala lapangan per `rambu_pasang` (alasan, foto) | `belongsTo` → `rambu_pasang`, `users` |
| `laporan_kondisi` | Temuan kondisi rambu (independen SPK aktif), status tindak lanjut | `belongsTo` → `rambu`, `users` |
| `rt_perwakilan` | Kontak RT/perwakilan warga per SPK (untuk tanda tangan manual di surat) | `belongsTo` → `spk` |
| `audit_log` | Jejak aksi penting, `spk_id` di-null-kan (bukan ikut terhapus) kalau SPK dihapus | `belongsTo` → `users`, `spk` |
| `notifikasi` | Notifikasi in-app per user | `belongsTo` → `users` |
| `system_error_log` | Exception tak terduga yang tertangkap otomatis oleh exception handler | `belongsTo` → `users` (opsional) |

Aturan hapus (foreign key) mengikuti prinsip: data yang jadi "milik" SPK/rambu_pasang ikut terhapus kalau induknya dihapus (`cascade`), sementara data referensi seperti `rambu`, `users`, dan `laporan_kondisi` **tidak boleh** terhapus kalau masih dirujuk (`restrict`), dan SPK sendiri **tidak pernah dihapus permanen**, hanya diubah statusnya jadi `dibatalkan`.

Setiap kolom status/jenis (`status`, `urgensi`, `kondisi_terkini`, `jenis_pekerjaan`, dst) disimpan sebagai string tapi selalu di-cast ke [PHP backed enum](app/Enums) di level model. Lihat daftar lengkap nilainya di `app/Enums/*.php`.

## Tumpukan Teknologi

| Layer | Teknologi |
|---|---|
| Backend | Laravel 13, PHP ^8.3 |
| UI reaktif | Livewire 4 (class-based components di `app/Livewire`, view di `resources/views/pages`) |
| Komponen UI | Flux UI 2 (free tier) + komponen custom (`searchable-select`, `searchable-multiselect`) untuk kebutuhan yang tidak ada di free tier |
| Auth | Laravel Fortify: login NIP, 2FA, konfirmasi password untuk halaman sensitif |
| Database | SQLite (dev), lewat Eloquent ORM |
| Peta | Leaflet.js + OpenStreetMap tiles |
| PDF | barryvdh/laravel-dompdf (Surat Pengantar & Laporan Bulanan) |
| CSS | Tailwind CSS 4 |
| Build | Vite |
| Test | PHPUnit (Feature test, `Livewire::test()` untuk komponen) |

## Cara Menjalankan (Setelah Clone)

### Prasyarat

- PHP **8.3** atau lebih baru, dengan ekstensi `pdo_sqlite`, `mbstring`, `dom` (wajib), dan `gd` (disarankan, dipakai DomPDF untuk merender foto yang ditempel di Surat Pengantar/Laporan Bulanan)
- Composer 2
- Node.js 22 + npm
- Ekstensi SQLite aktif di PHP

### Langkah instalasi

```bash
git clone https://github.com/Omelette719/MARLIN.git
cd MARLIN

# 1. Install dependency PHP & JS
composer install
npm install

# 2. Siapkan file environment
cp .env.example .env
php artisan key:generate

# 3. Siapkan database SQLite
touch database/database.sqlite
# (di Windows/PowerShell: New-Item database/database.sqlite -ItemType File)

# 4. Jalankan migrasi + seeder dasar
php artisan migrate --seed

# 5. Buat symlink storage (supaya foto upload bisa diakses browser)
php artisan storage:link

# 6. Build aset frontend
npm run build
# (atau `npm run dev` untuk mode pengembangan dengan hot-reload)

# 7. Jalankan server
php artisan serve
```

Buka `http://localhost:8000` (atau domain Herd/Valet kamu kalau pakai itu), lalu login pakai salah satu akun di bawah.

> **Catatan:** `database/seeders/DatabaseSeeder.php` secara default hanya membuat akun (5 admin + 1 petugas) dan master data jenis rambu, **tidak** ada data SPK/rambu contoh. Kalau butuh data contoh untuk demo/pengembangan, jalankan seeder demo secara terpisah:
> ```bash
> php artisan db:seed --class=MarlinDemoSeeder
> ```
> Seeder ini akan menambah beberapa petugas contoh, beberapa SPK dengan berbagai status, dan beberapa temuan kondisi. Aman dijalankan kapan saja karena tidak menyentuh akun admin/petugas yang sudah ada.

### Reset database dari awal

```bash
php artisan migrate:fresh --seed
```

## Akun Setelah Seeding

Semua akun hasil seeding memakai password default: **`password`**

| Role | Nama | NIP | Catatan |
|---|---|---|---|
| Admin | Admin Dishub | `1` | Admin utama, NIP sengaja dibuat pendek untuk memudahkan login saat pengembangan |
| Admin | (4 akun lain) | acak (lihat tabel `users`) | Dibuat via factory, NIP/username acak |
| Petugas | Test User | `2` | Satu-satunya akun petugas default |

Login di halaman `/login` menggunakan **NIP**, bukan email.

## Menjalankan Test

```bash
php artisan test
```

Test suite mencakup: alur auth (login/2FA/password confirmation), CRUD & validasi SPK, validasi pengerjaan (terima/tolak, termasuk perpanjangan deadline saat menolak), laporan kendala & pengerjaan, temuan kondisi, laporan bulanan, peta (endpoint data JSON), audit log, notifikasi, integrasi Telegram, dan generasi PDF surat pengantar/laporan. Total 301 test, 291 aktif dan 10 di-skip di lingkungan tanpa dependency tertentu.

Cek gaya kode (Pint):
```bash
composer lint:check   # cek saja, tidak mengubah file
composer lint          # cek sekaligus perbaiki otomatis
```

## Struktur Direktori Penting

```
app/
  Enums/            Semua backed enum (status, jenis, urgensi, dst)
  Concerns/         Trait bersama (mis. ComposesWilayah, auto-susun kolom wilayah dari jalan/rt/kelurahan)
  Http/Controllers/ Controller non-Livewire (generate PDF, data peta)
  Livewire/
    Admin/          Komponen khusus admin (Spk, Validasi, Users, Laporan, dst)
    User/           Komponen khusus petugas (Kendala, Laporan, Temuan, dst)
    Rambu/, Settings/   Komponen bersama
  Models/           Eloquent model + relasi untuk semua tabel domain
  Support/          Logic pendukung non-model (mis. LaporanBulanan)
database/
  migrations/       Satu file per tabel, urutan sesuai dependency FK
  seeders/          DatabaseSeeder (akun dasar), JenisRambuSeeder (master data), MarlinDemoSeeder (data contoh, opsional)
resources/
  views/pages/      View Blade, mengikuti struktur namespace Livewire component (lihat config/livewire.php)
  views/pdf/        Template PDF (surat pengantar, laporan bulanan)
  js/app.js         Inisialisasi peta Leaflet
tests/Feature/      Test per fitur (Admin/, User/, dan lainnya)
```

## CI/CD

Setiap push/PR ke `main` menjalankan 2 workflow GitHub Actions (`.github/workflows/`):
- **`tests.yml`**: install dependency, build aset, jalankan `phpunit` di matrix PHP 8.3/8.4/8.5.
- **`lint.yml`**: jalankan Pint untuk format kode.

`composer.json` mengunci `config.platform.php` ke `8.3.0` supaya `composer.lock` selalu resolve ke versi paket yang kompatibel dengan PHP minimum yang didukung (^8.3), terlepas dari versi PHP di komputer developer yang menjalankan `composer update`.

## Dokumentasi Lengkap

Dokumen ini cuma ringkasan. Untuk detail lebih dalam, lihat folder [`docs/`](docs/README.md):

| Dokumen | Isi |
|---|---|
| [docs/FITUR.md](docs/FITUR.md) | Rincian tiap fitur per peran |
| [docs/HALAMAN.md](docs/HALAMAN.md) | Referensi tiap halaman: URL, akses, fungsinya |
| [docs/ALUR-BISNIS.md](docs/ALUR-BISNIS.md) | Siklus hidup SPK & aturan bisnis secara detail |
| [docs/DAFTAR-AKTIVITAS.md](docs/DAFTAR-AKTIVITAS.md) | Katalog aktivitas/interaksi per pasangan aktor, bahan mentah untuk activity diagram |
| [docs/DATABASE.md](docs/DATABASE.md) | Skema lengkap tiap tabel & aturan foreign key |
| [docs/KEAMANAN.md](docs/KEAMANAN.md) | Langkah keamanan & keputusan desain terkait akses data |
