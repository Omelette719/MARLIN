# Skenario UAT (User Acceptance Testing) — Sistem MARLIN

Skenario pengujian penerimaan pengguna untuk peran **Admin** dan **Petugas Lapangan**, disusun mengikuti alur fitur yang benar-benar berjalan di sistem (lihat [FITUR.md](FITUR.md), [ALUR-BISNIS.md](ALUR-BISNIS.md), [DAFTAR-AKTIVITAS.md](DAFTAR-AKTIVITAS.md)). Kolom Kriteria Penerimaan ditulis sebagai kondisi lolos/gagal yang bisa dicentang langsung oleh penguji, bukan penjelasan umum.

Kode skenario (`UAT-ADM-xx` / `UAT-USR-xx`) dipakai konsisten supaya bisa dirujuk balik ke laporan bug atau catatan pengujian.

---

## Daftar Isi

- [Skenario UAT — Admin](#skenario-uat--admin)
  - [Autentikasi & Akun](#autentikasi--akun-admin)
  - [Dashboard](#dashboard-admin)
  - [Pengerjaan (SPK)](#pengerjaan-spk-admin)
  - [Validasi Laporan](#validasi-laporan-admin)
  - [Temuan Lapangan](#temuan-lapangan-admin)
  - [Rambu](#rambu-admin)
  - [Riwayat & Laporan](#riwayat--laporan-admin)
  - [Lainnya (Manajemen Petugas & Log Error)](#lainnya-manajemen-petugas--log-error-admin)
- [Skenario UAT — Petugas Lapangan](#skenario-uat--petugas-lapangan)
  - [Autentikasi & Akun](#autentikasi--akun-petugas)
  - [Pengerjaan (SPK)](#pengerjaan-spk-petugas)
  - [Eksekusi Lapangan](#eksekusi-lapangan-petugas)
  - [Rambu](#rambu-petugas)
  - [Laporan & Riwayat](#laporan--riwayat-petugas)
- [Skenario UAT — Fitur Bersama](#skenario-uat--fitur-bersama)

---

## Skenario UAT — Admin

### Autentikasi & Akun (Admin)

| Kode | Fitur | Skenario | Kriteria Penerimaan |
|---|---|---|---|
| UAT-ADM-01 | Login | Admin login dengan NIP + kata sandi yang benar | Berhasil masuk dan diarahkan ke Dashboard Admin |
| UAT-ADM-02 | Login | Admin login dengan kata sandi salah | Ditolak dengan pesan "NIP atau kata sandi yang Anda masukkan salah", tidak masuk sistem |
| UAT-ADM-03 | Login | Percobaan login gagal 6 kali berturut-turut dalam 1 menit dengan NIP+IP yang sama | Percobaan ke-6 diblokir dengan pesan "terlalu banyak percobaan", bukan pesan salah kata sandi |
| UAT-ADM-04 | Login | Akun admin yang sudah dinonaktifkan mencoba login | Ditolak dengan pesan "Akun ini telah dinonaktifkan...", tidak masuk sistem |
| UAT-ADM-05 | 2FA | Admin dengan 2FA aktif login lalu diminta kode verifikasi | Tidak bisa masuk dashboard sebelum kode 2FA yang benar dimasukkan |

### Dashboard (Admin)

| Kode | Fitur | Skenario | Kriteria Penerimaan |
|---|---|---|---|
| UAT-ADM-06 | Dashboard | Buka Dashboard Admin saat ada SPK aktif, rambu rusak, dan laporan menunggu validasi | Angka ringkasan (SPK aktif, rambu rusak, menunggu validasi) sesuai data sesungguhnya di database |
| UAT-ADM-07 | Dashboard — Widget Peta | Ubah filter jenis rambu/tingkat/tanggal di widget peta dashboard | Pin yang tampil di peta berubah sesuai filter, tanpa reload halaman |
| UAT-ADM-08 | Dashboard — Widget Peta | Klik "Unduh PDF" pada widget peta | File PDF sebaran rambu terunduh, berisi cuplikan peta sesuai filter aktif dan tabel analitik |

### Pengerjaan (SPK) (Admin)

| Kode | Fitur | Skenario | Kriteria Penerimaan |
|---|---|---|---|
| UAT-ADM-09 | Buat Surat | Isi form SPK lengkap dengan satu baris rambu **Pemasangan Baru** dan simpan | SPK tersimpan berstatus Aktif, seluruh petugas aktif menerima notifikasi "SPK Baru Tersedia" |
| UAT-ADM-10 | Buat Surat | Tambah baris kedua bertipe **Perbaikan** (pilih rambu yang sudah terdaftar) di SPK yang sama | Kedua baris tersimpan dengan jenis pekerjaan masing-masing; kartu SPK di Daftar Surat menampilkan badge **"Pemasangan & Perbaikan"** |
| UAT-ADM-11 | Buat Surat | Isi Deadline dengan tanggal hari ini atau tanggal yang sudah lewat | Ditolak dengan pesan "Deadline harus setelah hari ini", SPK tidak tersimpan |
| UAT-ADM-12 | Buat Surat | Isi field RT dengan huruf (mis. "5A") | Ditolak dengan pesan "RT hanya boleh berisi angka" sebelum tombol Simpan Surat ditekan |
| UAT-ADM-13 | Buat Surat | Tandai SPK sebagai Prioritas | Urgensi otomatis menjadi Tinggi, dan SPK aktif non-prioritas lain deadline-nya ikut mundur (maksimal, tidak akumulatif) |
| UAT-ADM-14 | Buat Surat dari Temuan | Klik "Buat SPK" dari sebuah Temuan Lapangan | Baris rambu otomatis terisi (rambu & foto dari temuan), jenis pekerjaan baris itu otomatis Perbaikan |
| UAT-ADM-15 | Edit Surat | Ubah data SPK yang masih berstatus Aktif | Perubahan tersimpan dan tercatat di Audit Log (`spk_diedit`) |
| UAT-ADM-16 | Edit Surat | Coba akses halaman Edit untuk SPK yang sudah Selesai/Dibatalkan | Ditolak (403), tidak bisa membuka form edit |
| UAT-ADM-17 | Edit Surat | Ubah jenis pekerjaan satu baris rambu yang sudah ada (mis. dari Pasang Baru ke Perbaikan) | Perubahan tersimpan, baris tersebut menampilkan jenis pekerjaan yang baru |
| UAT-ADM-18 | Batalkan SPK | Batalkan seluruh SPK yang masih Aktif | Status jadi Dibatalkan (bukan terhapus), semua rambu belum-selesai ikut Batal, tim yang sudah bergabung dapat notifikasi |
| UAT-ADM-19 | Batalkan Satu Rambu | Batalkan satu baris rambu saja dari Edit Surat, isi alasan | Hanya rambu itu berstatus Batal, rambu lain di SPK yang sama tidak terganggu, alasan tampil di kartu rambu |
| UAT-ADM-20 | Hapus Rambu | Hapus permanen baris rambu yang masih berstatus Belum dan belum ada laporan/kendala | Baris rambu_pasang terhapus dari surat, data rambu fisiknya tetap ada di sistem |

### Validasi Laporan (Admin)

| Kode | Fitur | Skenario | Kriteria Penerimaan |
|---|---|---|---|
| UAT-ADM-21 | Antrean Validasi | Buka halaman Validasi Laporan | Hanya menampilkan SPK yang sudah mengajukan Laporan Akhir |
| UAT-ADM-22 | Validasi — Terima | Terima sebuah laporan pengerjaan yang diajukan petugas | Status rambu jadi Selesai; untuk Pasang Baru `sudah_terpasang` jadi true, untuk Perbaikan `kondisi_terkini` jadi baik |
| UAT-ADM-23 | Validasi — Tolak | Tolak sebuah laporan tanpa mengisi catatan penolakan | Ditolak sistem, wajib isi catatan dulu sebelum submit |
| UAT-ADM-24 | Validasi — Tolak | Tolak laporan dengan catatan penolakan terisi | Status rambu jadi Revisi, petugas menerima notifikasi berisi catatan tersebut |
| UAT-ADM-25 | Validasi — Kendala | Coba tandai rambu berstatus Tertunda (kendala) sebagai diterima | Kartu kendala tidak bisa diklik/dicentang sama sekali; begitu diproses, otomatis masuk jalur revisi |
| UAT-ADM-26 | Validasi — Perpanjang Deadline | Saat menolak laporan, centang "Beri kelonggaran, perpanjang deadline" dan isi tanggal baru | Deadline SPK berubah, urgensi terhitung ulang, seluruh tim menerima notifikasi perubahan deadline |
| UAT-ADM-27 | Validasi — SPK Selesai Otomatis | Terima/tolak-lalu-terima seluruh rambu dalam satu SPK hingga semuanya Selesai/Batal | Status SPK otomatis berubah jadi Selesai, `selesai_pada` tercatat |

### Temuan Lapangan (Admin)

| Kode | Fitur | Skenario | Kriteria Penerimaan |
|---|---|---|---|
| UAT-ADM-28 | Temuan Lapangan | Buka antrean Temuan Lapangan | Hanya menampilkan temuan berstatus "baru" (belum ditindaklanjuti) |
| UAT-ADM-29 | Tindak Lanjut — Buat SPK | Buat SPK perbaikan dari sebuah temuan | SPK baru tercipta, status temuan jadi "sudah dibuatkan SPK", foto temuan otomatis jadi foto survei rambu di SPK baru |
| UAT-ADM-30 | Tindak Lanjut — Tolak | Tolak sebuah temuan | Status temuan jadi Ditolak, petugas pelapor menerima notifikasi |

### Rambu (Admin)

| Kode | Fitur | Skenario | Kriteria Penerimaan |
|---|---|---|---|
| UAT-ADM-31 | Jenis Rambu | Tambah jenis rambu baru dengan nama mengandung angka (mis. "Rambu 123") | Ditolak, nama jenis rambu hanya boleh huruf dan spasi |
| UAT-ADM-32 | Jenis Rambu | Tambah/ubah/hapus jenis rambu dengan data valid | Perubahan tersimpan dan langsung terlihat di daftar jenis rambu |
| UAT-ADM-33 | Daftar Rambu | Filter daftar rambu berdasarkan kondisi (baik/rusak) dan wilayah | Hasil daftar sesuai filter yang dipilih |

### Riwayat & Laporan (Admin)

| Kode | Fitur | Skenario | Kriteria Penerimaan |
|---|---|---|---|
| UAT-ADM-34 | Riwayat SPK | Buka Riwayat SPK, filter status Selesai/Dibatalkan | Hanya SPK dengan status sesuai filter yang tampil, SPK Aktif tidak muncul di sini |
| UAT-ADM-35 | Riwayat SPK | Buka Detail Surat untuk SPK berstatus Selesai | Menampilkan Durasi Pengerjaan dan Selisih dari Deadline (badge hijau jika lebih cepat, merah jika terlambat) |
| UAT-ADM-36 | Riwayat Aktivitas | Buka Riwayat Aktivitas (Audit Log) sebagai admin | Menampilkan aksi bisnis dari **seluruh pengguna**, bukan hanya admin yang login |
| UAT-ADM-37 | Laporan Bulanan | Atur filter rentang tanggal/jenis rambu/status lalu unduh PDF | File PDF terunduh berisi rekap sesuai filter yang diterapkan |
| UAT-ADM-38 | Laporan Rambu | Atur filter lalu unduh PDF riwayat pekerjaan per rambu | File PDF terunduh, angka konsisten dengan Laporan Bulanan untuk filter yang sama |

### Lainnya (Manajemen Petugas & Log Error) (Admin)

| Kode | Fitur | Skenario | Kriteria Penerimaan |
|---|---|---|---|
| UAT-ADM-39 | Manajemen Petugas | Tambah akun petugas baru dengan data lengkap | Akun baru langsung bisa dipakai login |
| UAT-ADM-40 | Manajemen Petugas | Nonaktifkan akun petugas yang sedang tidak login | Akun tersebut gagal login pada percobaan berikutnya dengan pesan akun dinonaktifkan |
| UAT-ADM-41 | Manajemen Petugas | Isi Nama/Nama Panggilan dengan angka | Ditolak, field tersebut hanya boleh huruf dan spasi |
| UAT-ADM-42 | Log Error Sistem | Terjadi exception tak tertangani (level 500) di aplikasi | Baris baru muncul otomatis di System Error Log tanpa aksi manual admin |

---

## Skenario UAT — Petugas Lapangan

### Autentikasi & Akun (Petugas)

| Kode | Fitur | Skenario | Kriteria Penerimaan |
|---|---|---|---|
| UAT-USR-01 | Login | Petugas login dengan NIP + kata sandi benar | Berhasil masuk, diarahkan ke Daftar Surat Aktif |
| UAT-USR-02 | Reset Password | Buka wizard reset password di halaman login, verifikasi NIP + kata sandi lama, lalu set kata sandi baru | Kata sandi berhasil diganti, bisa login dengan kata sandi baru |
| UAT-USR-03 | Reset Password | Coba verifikasi identitas 6 kali dengan kata sandi lama yang salah dalam 1 menit | Percobaan ke-6 diblokir sementara |

### Pengerjaan (SPK) (Petugas)

| Kode | Fitur | Skenario | Kriteria Penerimaan |
|---|---|---|---|
| UAT-USR-04 | Daftar Surat Aktif | Buka dashboard petugas | Menampilkan **semua** SPK berstatus Aktif, bukan hanya yang sudah diikuti tim sendiri |
| UAT-USR-05 | Gabung Tim | Daftarkan diri sebagai perwakilan pada SPK yang belum ada timnya, sekaligus pilih rekan setim | Tim tercatat, kartu SPK menampilkan badge "Sudah Bergabung" |
| UAT-USR-06 | Gabung Tim | Dua petugas mencoba mendaftarkan tim pada SPK yang sama hampir bersamaan | Petugas kedua menerima toast peringatan "sudah didaftarkan orang lain" dan halaman dimuat ulang, tidak membuat tim ganda |
| UAT-USR-07 | Kelola Tim | Sebagai perwakilan, tambah anggota baru ke tim yang sudah ada | Anggota baru tercatat, tampil di daftar Tim Bertugas |
| UAT-USR-08 | Kelola Tim | Sebagai perwakilan, hapus seorang anggota (bukan perwakilan) dari tim | Anggota terhapus dari tim, dia menerima notifikasi "Dikeluarkan dari Tim" |
| UAT-USR-09 | Kelola Tim | Coba tambah/hapus anggota tim pada SPK yang **sudah Selesai** | Tombol tambah/hapus anggota tidak muncul, aksi ditolak walau dicoba langsung |
| UAT-USR-10 | Surat Pengantar | Klik tombol unduh Surat Pengantar pada SPK yang **belum diikuti** timnya | Muncul toast "Gabung dulu ke tim surat ini...", tidak diarahkan ke halaman error/forbidden |
| UAT-USR-11 | Surat Pengantar | Unduh Surat Pengantar pada SPK yang sudah diikuti tim | File PDF terunduh, berisi data surat, daftar rambu, dan tim |

### Eksekusi Lapangan (Petugas)

| Kode | Fitur | Skenario | Kriteria Penerimaan |
|---|---|---|---|
| UAT-USR-12 | Laporan Pengerjaan | Sebagai perwakilan, kirim Form Laporan Pengerjaan tanpa mengisi foto sesudah | Ditolak dengan pesan error jelas, laporan tidak terkirim |
| UAT-USR-13 | Laporan Pengerjaan | Ambil lokasi GPS lewat tombol "Ambil Lokasi Sekarang" saat izin lokasi browser **ditolak** | Muncul toast merah menjelaskan izin ditolak dan cara mengaktifkannya, bukan tombol yang diam saja |
| UAT-USR-14 | Laporan Pengerjaan | Ambil lokasi GPS dengan izin diberikan, lalu kirim laporan lengkap (foto + GPS + catatan) | Koordinat terisi otomatis, laporan terkirim, status rambu jadi Menunggu Validasi |
| UAT-USR-15 | Form Kendala | Sebagai perwakilan, kirim Form Kendala dengan foto dan alasan terisi | Status rambu jadi Tertunda, alasan kendala langsung terlihat di Detail SPK |
| UAT-USR-16 | Tukar Kendala/Laporan | Ubah rambu yang berstatus Tertunda (ada kendala) menjadi mengisi Laporan Pengerjaan | Kendala lama terhapus, laporan baru tercatat, status jadi Menunggu Validasi |
| UAT-USR-17 | Non-Perwakilan | Anggota tim yang **bukan** perwakilan mencoba mengisi Laporan Pengerjaan/Kendala | Aksi ditolak sistem, tidak ada laporan/kendala yang tersimpan |
| UAT-USR-18 | Ajukan Laporan Akhir | Coba ajukan Laporan Akhir saat masih ada rambu berstatus Belum/Revisi | Tombol "Ajukan Laporan Akhir" tidak muncul/tidak bisa diklik |
| UAT-USR-19 | Ajukan Laporan Akhir | Ajukan Laporan Akhir setelah semua rambu berstatus Tertunda/Menunggu Validasi/Selesai | SPK masuk antrean Validasi Laporan admin, admin pembuat SPK menerima notifikasi |

### Rambu (Petugas)

| Kode | Fitur | Skenario | Kriteria Penerimaan |
|---|---|---|---|
| UAT-USR-20 | Peta Rambu | Buka Peta Rambu dan klik salah satu pin | Kartu info (foto, jenis rambu, lokasi, status) terbuka, warna pin sesuai status terkini |
| UAT-USR-21 | Lapor Temuan dari Peta | Klik "Lapor Temuan Kondisi" dari kartu info pin, isi foto wajib | Kondisi rambu berubah jadi Rusak, pin berubah warna, seluruh admin menerima notifikasi |
| UAT-USR-22 | Daftar/Jenis Rambu | Buka halaman Daftar Rambu / Jenis Rambu sebagai petugas | Data tampil, tidak ada tombol tambah/ubah/hapus (read-only) |

### Laporan & Riwayat (Petugas)

| Kode | Fitur | Skenario | Kriteria Penerimaan |
|---|---|---|---|
| UAT-USR-23 | Riwayat Pekerjaan Saya | Buka Riwayat Pekerjaan Saya, filter per bulan | Hanya menampilkan SPK yang pernah dikerjakan tim sendiri sesuai bulan yang dipilih |
| UAT-USR-24 | Riwayat Aktivitas | Buka Riwayat Aktivitas sebagai petugas | Hanya menampilkan aktivitas yang dilakukan **oleh dirinya sendiri**, bukan aktivitas admin atau rekan setim |

---

## Skenario UAT — Fitur Bersama

Berlaku untuk kedua peran (Admin dan Petugas), diuji sekali per peran bila perilakunya bisa berbeda.

| Kode | Fitur | Skenario | Kriteria Penerimaan |
|---|---|---|---|
| UAT-BSM-01 | Notifikasi | Terjadi peristiwa yang relevan (mis. SPK baru, hasil validasi) | Badge merah di ikon lonceng bertambah, notifikasi baru muncul di daftar |
| UAT-BSM-02 | Notifikasi | Klik kartu notifikasi yang punya tujuan halaman | Halaman terkait terbuka dan notifikasi otomatis ditandai terbaca |
| UAT-BSM-03 | Telegram | Hubungkan akun ke bot Telegram lewat token sekali pakai | `telegram_chat_id` tersimpan, notifikasi berikutnya juga terkirim sebagai pesan Telegram |
| UAT-BSM-04 | Telegram | Putuskan hubungan akun Telegram yang sudah terhubung | Notifikasi Telegram berhenti terkirim, in-app tetap berjalan normal |
| UAT-BSM-05 | Profil | Ubah nama/nama panggilan/foto profil sendiri | Perubahan tersimpan dan langsung terlihat di header ("Halo, ...") |
| UAT-BSM-06 | Keamanan | Buka Settings → Keamanan tanpa konfirmasi ulang kata sandi | Diminta konfirmasi kata sandi dulu sebelum halaman bisa diakses, walau sesi login masih aktif |
| UAT-BSM-07 | Keamanan | Ganti kata sandi lewat Settings → Keamanan | Kata sandi baru aktif, bisa dipakai login berikutnya |
| UAT-BSM-08 | Logout | Klik Logout | Sesi berakhir, diarahkan ke halaman login, akses ke halaman terproteksi ditolak setelahnya |

---

## Catatan Penggunaan

- Kolom **Kriteria Penerimaan** ditulis sebagai kondisi tunggal yang bisa dicentang lolos/gagal — kalau sebuah skenario butuh beberapa kondisi sekaligus (mis. UAT-ADM-18), semuanya harus terpenuhi bersamaan untuk dinyatakan **Lolos**.
- Skenario yang menyentuh perubahan terbaru (jenis pekerjaan per baris, guard tim SPK selesai, toast surat pengantar, error GPS) sengaja dimasukkan karena ini titik-titik yang paling baru diverifikasi dan paling rawan regresi kalau ada perubahan berikutnya.
- Dokumen ini fokus ke skenario fungsional; untuk detail teknis di balik tiap aturan bisnis, lihat [ALUR-BISNIS.md](ALUR-BISNIS.md). Untuk daftar lengkap tiap interaksi (termasuk yang tidak masuk UAT karena risikonya rendah), lihat [DAFTAR-AKTIVITAS.md](DAFTAR-AKTIVITAS.md).
