# Penjelasan Halaman Sistem MARLIN

Referensi tiap halaman/route dalam sistem: URL, siapa yang bisa akses, dan apa fungsinya. Untuk penjelasan fitur per topik, lihat [FITUR.md](FITUR.md).

Semua halaman berada di bawah middleware `auth` (harus login), kecuali disebutkan lain. Halaman berlabel **Admin** dijaga middleware `role:admin`; **Petugas** dijaga `role:user`; **Bersama** bisa diakses siapa saja yang sudah login, apapun perannya.

---

## Autentikasi

| Halaman | Route | Akses | Keterangan |
|---|---|---|---|
| Login | `GET /login` | Publik (guest) | Login pakai NIP + password, bukan email. |
| Verifikasi 2FA | `GET /two-factor-challenge` | Guest, dalam proses login | Muncul kalau akun sudah mengaktifkan 2FA. |
| Konfirmasi Password | `GET /user/confirm-password` | Auth | Gate sebelum masuk ke halaman sensitif (Settings &rarr; Security). |
| Reset Password (modal) | — (modal di halaman login) | Publik | Wizard 2 langkah: verifikasi NIP+password lama, lalu set password baru. Dibatasi 5 percobaan/menit per NIP+IP. |

## Dashboard

| Halaman | Route | Akses | Keterangan |
|---|---|---|---|
| Dashboard Admin | `GET /admin/dashboard` | Admin | Ringkasan SPK aktif, rambu rusak, laporan menunggu validasi. |
| Daftar Surat Aktif (Dashboard Petugas) | `GET /dashboard` | Petugas | **Semua** SPK aktif (bukan cuma yang diikuti) — tempat menemukan pekerjaan baru. |

## Manajemen SPK (Admin)

| Halaman | Route | Akses | Keterangan |
|---|---|---|---|
| Daftar Surat | `GET /admin/spk` | Admin | List SPK, filter status/jenis, pencarian. SPK "Selesai" diarsipkan dari tampilan default. |
| Buat Surat | `GET /admin/spk/create` | Admin | Form buat SPK baru (pasang baru/perbaikan). |
| Detail Surat | `GET /admin/spk/{spk}` | Admin | Detail lengkap SPK, tim bertugas, daftar rambu, tombol Edit & Batalkan. |
| Edit Surat | `GET /admin/spk/{spk}/edit` | Admin | Hanya bisa diakses kalau SPK masih berstatus Aktif — 403 kalau sudah Selesai/Dibatalkan. |
| Validasi Laporan | `GET /admin/validasi` | Admin | Daftar SPK yang sudah mengajukan Laporan Akhir, siap divalidasi. |
| Detail Validasi | `GET /admin/validasi/{spk}` | Admin | Terima/tolak laporan per rambu. |
| Temuan Lapangan | `GET /admin/temuan` | Admin | Antrean laporan kondisi rusak yang belum dibuatkan SPK. |
| Laporan Bulanan | `GET /admin/laporan` | Admin | Rekap bulanan, bisa export PDF (`GET /admin/laporan/export`). |

## Manajemen Master Data & Pengguna (Admin)

| Halaman | Route | Akses | Keterangan |
|---|---|---|---|
| Manajemen Petugas | `GET /admin/users` | Admin | Daftar semua akun. |
| Tambah Petugas | `GET /admin/users/create` | Admin | Buat akun baru. |
| Edit Petugas | `GET /admin/users/{user}/edit` | Admin | Ubah data akun, aktifkan/nonaktifkan. |
| Jenis Rambu (admin) | `GET /admin/jenis-rambu` | Admin | Sama persis dengan versi bersama di bawah, cuma URL berbeda (`/admin/jenis-rambu` vs `/jenis-rambu`) — CRUD hanya berfungsi untuk admin walau petugas bisa buka URL yang sama. |
| Daftar Rambu (admin) | `GET /admin/rambu` | Admin | Sama persis dengan versi bersama (`/rambu`), URL alternatif. |
| Log Error Sistem | `GET /admin/system-error-log` | Admin | Exception tak terduga (level 500+) yang tertangkap otomatis. |

## Petugas Lapangan

| Halaman | Route | Akses | Keterangan |
|---|---|---|---|
| SPK Sedang Dikerjakan | `GET /spk-dikerjakan` | Petugas | Subset dari Dashboard — hanya SPK yang timnya sudah gabung. |
| Detail SPK (petugas) | `GET /spk/{spk}` | Petugas | **Sengaja terbuka untuk SPK aktif manapun** (bukan cuma yang diikuti) — perlu untuk alur "lihat dulu sebelum gabung tim". Lihat [KEAMANAN.md](KEAMANAN.md) untuk penjelasan keputusan desain ini. |
| Laporan Kendala | `GET /kendala` | Petugas | Form lapor kendala pemasangan/perbaikan. |
| Laporan Pengerjaan | `GET /laporan` | Petugas | Form laporan hasil kerja (foto, GPS, barang/bahan). |
| Riwayat Pekerjaan Saya | `GET /riwayat-spk` | Petugas | SPK yang pernah dikerjakan tim, filter per bulan. |
| Lapor Temuan Kondisi | `GET /temuan` | Petugas | Lapor rambu rusak, independen dari SPK aktif. |

## Bersama (Admin & Petugas)

| Halaman | Route | Akses | Keterangan |
|---|---|---|---|
| Peta Rambu | `GET /peta` | Siapa saja (auth) | Peta interaktif seluruh rambu; `?focus={rambu_id}` untuk auto-zoom & buka kartu rambu tertentu. |
| Data Peta (JSON) | `GET /peta/data` | Siapa saja (auth) | Endpoint JSON yang dikonsumsi JS peta — bukan halaman, dipanggil via fetch. |
| Detail Rambu | `GET /rambu/{rambu}` | Siapa saja (auth) | Riwayat foto & pekerjaan rambu; tombol Ke Validasi (khusus admin, kalau relevan). |
| Jenis Rambu | `GET /jenis-rambu` | Siapa saja (auth) | Referensi kategori rambu. |
| Daftar Rambu | `GET /rambu` | Siapa saja (auth) | Daftar semua rambu terpasang. |
| Surat Pengantar (PDF) | `GET /spk/{spk}/surat-pengantar` | Admin, atau petugas anggota tim SPK tsb. | **Satu-satunya halaman terkait-SPK yang membatasi akses per-keanggotaan tim**, bukan cuma per-peran — lihat [KEAMANAN.md](KEAMANAN.md). |
| Notifikasi | `GET /notifikasi` | Siapa saja (auth) | Daftar notifikasi in-app milik sendiri. |
| Riwayat Aktivitas | `GET /audit-log` | Siapa saja (auth) | Admin lihat semua; petugas cuma lihat aksinya sendiri. |
| Settings &rarr; Profil | `GET /settings/profile` | Siapa saja (auth) | Ubah nama, foto, data diri. |
| Settings &rarr; Keamanan | `GET /settings/security` | Siapa saja (auth) | Ganti password, kelola 2FA — **butuh konfirmasi ulang password** sebelum bisa diakses. |

---

## Catatan Penting

- **`/rambu` dan `/jenis-rambu` didaftarkan dua kali** — sekali di luar prefix `admin/` (bisa diakses siapa saja yang login), sekali lagi di dalam grup `admin/` (URL `/admin/rambu` dan `/admin/jenis-rambu`, admin-only). Keduanya memakai komponen Livewire yang sama persis; ini bukan duplikasi berbahaya, cuma dua alamat URL untuk halaman yang sama. **Daftar Rambu** memang murni read-only (tidak ada aksi ubah/hapus sama sekali di komponennya, jadi tidak ada yang perlu dijaga selain tampilan). **Jenis Rambu** punya aksi tambah/ubah/hapus, dan komponennya sendiri memanggil `abort_unless(Auth::user()->isAdmin(), 403)` di tiap aksi tersebut — jadi walau petugas membuka `/jenis-rambu`, tombol create/edit/delete tidak akan berfungsi untuknya (403), bukan cuma disembunyikan di tampilan.
- **Semua route berada di bawah middleware `auth`** kecuali halaman login/2FA-challenge/reset-password (yang memang harus bisa diakses sebelum login). Tidak ada halaman data yang bisa diakses tanpa login sama sekali.
