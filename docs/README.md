# Dokumentasi Sistem MARLIN

Dokumentasi lengkap sistem, dipecah per topik. Untuk gambaran umum & cara instalasi, mulai dari [README.md](../README.md) di root proyek.

| Dokumen | Isi |
|---|---|
| [FITUR.md](FITUR.md) | Rincian tiap fitur, dikelompokkan per peran (admin/petugas/bersama) |
| [HALAMAN.md](HALAMAN.md) | Referensi tiap halaman: URL, siapa yang bisa akses, fungsinya |
| [ALUR-BISNIS.md](ALUR-BISNIS.md) | Siklus hidup SPK & aturan bisnis secara detail |
| [DAFTAR-AKTIVITAS.md](DAFTAR-AKTIVITAS.md) | Katalog aktivitas/interaksi per pasangan aktor (Petugas-Sistem, Admin-Sistem, Admin-Petugas, dll), bahan mentah untuk activity diagram |
| [DATABASE.md](DATABASE.md) | Skema lengkap tiap tabel, kolom, dan aturan foreign key |
| [KEAMANAN.md](KEAMANAN.md) | Langkah keamanan yang diterapkan & keputusan desain terkait akses data |

## Urutan Baca yang Disarankan

Kalau baru pertama kali kenal proyek ini:
1. [README.md](../README.md): apa itu MARLIN, cara jalankan
2. [ALUR-BISNIS.md](ALUR-BISNIS.md): memahami siklus SPK dari awal sampai akhir
3. [FITUR.md](FITUR.md): detail tiap fitur per peran
4. [HALAMAN.md](HALAMAN.md): peta URL & akses
5. [DAFTAR-AKTIVITAS.md](DAFTAR-AKTIVITAS.md): katalog aktivitas per pasangan aktor, kalau mau susun activity diagram
6. [DATABASE.md](DATABASE.md): kalau perlu ubah/tambah skema
7. [KEAMANAN.md](KEAMANAN.md): sebelum deploy ke produksi
