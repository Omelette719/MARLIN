# Struktur Bab & Subbab — Buku Panduan Pengguna Sistem MARLIN

Rincian bab/subbab untuk penyusunan **Buku Panduan Pengguna** (user manual) Sistem MARLIN, disusun mengikuti struktur menu sidebar yang benar-benar ada di aplikasi (per peran Admin dan Petugas Lapangan), supaya pembaca bisa mengikuti buku panduan sambil membuka sistemnya secara berurutan. Sumber kebenaran fitur: [FITUR.md](FITUR.md), [HALAMAN.md](HALAMAN.md), [ALUR-BISNIS.md](ALUR-BISNIS.md), [KEAMANAN.md](KEAMANAN.md).

Ini adalah **outline/rincian bab**, bukan isi lengkap buku panduannya — tiap subbab diberi catatan singkat berisi apa yang perlu ditulis di dalamnya.

---

## BAB I — PENDAHULUAN

### 1.1 Tentang Sistem MARLIN
Gambaran umum: sistem manajemen rambu lalu lintas untuk Dinas Perhubungan Kota Banjarmasin, menggantikan koordinasi manual lewat WhatsApp antara pengawas (Admin) dan petugas lapangan.

### 1.2 Tujuan Buku Panduan
Membantu pengguna baru (Admin maupun Petugas) memahami cara menjalankan tugasnya lewat sistem, dari login sampai menyelesaikan pekerjaan.

### 1.3 Peran Pengguna
Penjelasan dua peran: **Admin** (mengelola SPK, memvalidasi laporan, mengelola master data) dan **Petugas Lapangan** (mengerjakan SPK, melapor hasil kerja).

### 1.4 Istilah & Singkatan (Glosarium Awal)
SPK, Rambu Pasang, Perwakilan, Laporan Akhir, Urgensi, dll. — daftar penuh ada di Lampiran (Bab VII).

### 1.5 Kebutuhan Perangkat
Browser modern (Chrome/Firefox/Edge), koneksi internet, smartphone dengan GPS & kamera aktif untuk Petugas Lapangan.

---

## BAB II — MEMULAI PENGGUNAAN SISTEM

### 2.1 Mengakses Sistem
Alamat/URL sistem, catatan bahwa akun dibuat oleh Admin (tidak ada pendaftaran mandiri).

### 2.2 Login
Langkah login pakai NIP + kata sandi.

### 2.3 Verifikasi Dua Langkah (2FA)
Langkah masuk kode verifikasi bagi akun yang sudah mengaktifkan 2FA.

### 2.4 Lupa Kata Sandi
Langkah reset kata sandi mandiri lewat wizard di halaman login (butuh NIP + kata sandi lama).

### 2.5 Mengenal Tampilan Utama
Penjelasan bagian antarmuka: sidebar (menu tergantung peran), header (notifikasi, profil), area konten utama.

### 2.6 Logout
Cara keluar dari sistem dengan aman.

---

## BAB III — PANDUAN PENGGUNAAN UNTUK ADMIN

### 3.1 Dashboard
Ringkasan SPK aktif, rambu rusak, laporan menunggu validasi, dan widget peta ringkas.

### 3.2 Peta
Melihat sebaran rambu di peta interaktif, memakai filter, membaca warna pin, mengunduh PDF sebaran rambu.

### 3.3 Pengerjaan
#### 3.3.1 Daftar Surat
Melihat daftar SPK yang sedang aktif, mencari & memfilter.
#### 3.3.2 Buat Surat
Langkah lengkap membuat SPK baru: pilih jenis (pasang baru/perbaikan), isi alamat, deadline, daftar rambu, upload file referensi.
#### 3.3.3 Mengedit & Membatalkan Surat
Mengubah data SPK yang masih aktif; membatalkan seluruh SPK atau satu rambu saja beserta alasannya.
#### 3.3.4 Validasi Laporan
Meninjau laporan yang masuk dari petugas, menerima/menolak per rambu, opsi memberi kelonggaran deadline.
#### 3.3.5 Temuan Lapangan
Meninjau laporan kondisi rusak dari petugas, menindaklanjuti jadi SPK perbaikan atau menolaknya.

### 3.4 Rambu
#### 3.4.1 Daftar Rambu
Melihat seluruh rambu terdaftar beserta kondisinya, memfilter berdasar wilayah/kondisi.
#### 3.4.2 Jenis Rambu
Mengelola master data jenis rambu (tambah/ubah/hapus, spesifikasi, gambar referensi).

### 3.5 Riwayat & Laporan
#### 3.5.1 Riwayat SPK
Melihat arsip SPK yang sudah selesai/dibatalkan.
#### 3.5.2 Riwayat Aktivitas
Melihat jejak aksi bisnis kunci seluruh pengguna (audit log).
#### 3.5.3 Laporan Bulanan
Membuat & mengunduh rekap pekerjaan rambu per rentang tanggal (PDF).
#### 3.5.4 Laporan Rambu
Membuat & mengunduh riwayat pekerjaan per rambu (PDF).

### 3.6 Lainnya
#### 3.6.1 Manajemen Petugas
Menambah, mengubah, mengaktifkan/menonaktifkan akun petugas dan admin.
#### 3.6.2 Log Error Sistem
Melihat catatan error teknis yang tertangkap otomatis oleh sistem.

---

## BAB IV — PANDUAN PENGGUNAAN UNTUK PETUGAS LAPANGAN

### 4.1 Pengerjaan
#### 4.1.1 Daftar Surat Aktif
Halaman awal petugas: melihat semua SPK aktif yang bisa diambil, bukan cuma yang sudah diikuti.
#### 4.1.2 Melihat Detail SPK & Bergabung Tim
Membuka detail SPK, mendaftarkan diri sebagai perwakilan beserta rekan setim, menambah/menghapus anggota belakangan.
#### 4.1.3 Mengisi Laporan Pengerjaan
Langkah kirim laporan hasil kerja: foto sesudah, koordinat GPS, catatan, daftar barang/bahan. (khusus perwakilan tim)
#### 4.1.4 Mengajukan Kendala
Langkah melapor kalau pekerjaan tidak bisa dilanjutkan: foto + alasan wajib. (khusus perwakilan tim)
#### 4.1.5 Mengajukan Laporan Akhir
Kapan tombol ini muncul (semua rambu sudah tertangani) dan apa yang terjadi setelah diajukan.
#### 4.1.6 SPK Sedang Dikerjakan
Melihat daftar SPK yang timnya sendiri sudah bergabung.

### 4.2 Rambu
#### 4.2.1 Peta Rambu
Melihat sebaran rambu di peta, melapor temuan langsung dari kartu info pin.
#### 4.2.2 Daftar Rambu
Melihat daftar rambu terdaftar (read-only).
#### 4.2.3 Jenis Rambu
Melihat referensi jenis rambu (read-only).

### 4.3 Laporan & Riwayat
#### 4.3.1 Laporan Temuan Kondisi
Melapor rambu yang ditemukan rusak di lapangan, independen dari SPK aktif manapun.
#### 4.3.2 Riwayat Pekerjaan Saya
Melihat SPK yang pernah dikerjakan tim, difilter per bulan.
#### 4.3.3 Riwayat Aktivitas
Melihat jejak aktivitas yang dilakukan sendiri.

---

## BAB V — FITUR BERSAMA (ADMIN & PETUGAS)

### 5.1 Detail Rambu
Melihat riwayat foto, kondisi, dan pekerjaan satu rambu tertentu.

### 5.2 Notifikasi
Membaca notifikasi lewat ikon lonceng, badge belum-dibaca, klik kartu notifikasi untuk membuka halaman terkait.

### 5.3 Menghubungkan Akun Telegram
Langkah menghubungkan akun ke bot Telegram supaya notifikasi juga masuk sebagai pesan chat; cara memutuskannya.

### 5.4 Mengelola Profil
Mengubah nama, nama panggilan, foto profil.

### 5.5 Mengelola Keamanan Akun
Mengganti kata sandi, mengaktifkan/menonaktifkan 2FA (perlu konfirmasi ulang kata sandi).

### 5.6 Mengunduh Surat Pengantar
Cara mengunduh dokumen surat pengantar (PDF) dari Detail SPK — tersedia untuk Admin dan anggota tim SPK terkait.

---

## BAB VI — PENANGANAN MASALAH (TROUBLESHOOTING)

### 6.1 Lupa Kata Sandi
Solusi lewat wizard reset di halaman login.

### 6.2 Akun Tidak Bisa Login / Dinonaktifkan
Penjelasan pesan error umum dan langkah menghubungi Admin.

### 6.3 Tombol "Ambil Lokasi Sekarang" Tidak Berfungsi
Cara mengaktifkan izin lokasi di pengaturan browser kalau sebelumnya ditolak.

### 6.4 Belum Bisa Mengunduh Surat Pengantar
Penjelasan bahwa unduhan hanya aktif untuk anggota tim yang sudah bergabung.

### 6.5 Notifikasi Telegram Tidak Masuk
Langkah memastikan akun sudah terhubung (tekan Start di bot) dan cara menghubungkan ulang.

### 6.6 Kontak Bantuan
Ke mana menghubungi kalau kendala belum teratasi (Admin sistem/Dishub).

---

## BAB VII — LAMPIRAN

### 7.1 Glosarium Lengkap
Daftar istilah lengkap: SPK, Rambu Pasang, Perwakilan, Kendala, Laporan Akhir, Urgensi, Temuan Kondisi, dst.

### 7.2 Daftar Kode Status
Tabel penjelasan status rambu (Belum/Urgent/Tertunda/Menunggu Validasi/Revisi/Selesai/Batal), urgensi (Rendah/Sedang/Tinggi), dan arti warna pin peta.

### 7.3 Pertanyaan yang Sering Diajukan (FAQ)
Kumpulan pertanyaan umum dari kedua peran, mis. "Bisakah satu rambu punya dua laporan?", "Kenapa saya tidak bisa menghapus anggota tim di SPK yang sudah selesai?".

### 7.4 Kontak & Dukungan Teknis
Informasi kontak tim IT/Admin Dishub untuk eskalasi masalah teknis.

---

## Catatan Penyusunan

- Urutan Bab III (Admin) dan Bab IV (Petugas) sengaja mengikuti persis urutan grup menu sidebar masing-masing peran, supaya pembaca bisa membuka buku panduan sambil mengklik menu yang sama urutannya di sistem.
- Fitur yang muncul di kedua peran (Peta, Detail Rambu, Notifikasi, Telegram, Profil, Keamanan) sengaja dikumpulkan jadi satu di Bab V, bukan diulang di Bab III dan IV, supaya isi buku panduan tidak duplikat.
- Bab VI (Troubleshooting) berisi masalah yang benar-benar pernah ditemukan/ diperbaiki selama pengembangan (lihat riwayat commit terkait), bukan skenario hipotetis.
