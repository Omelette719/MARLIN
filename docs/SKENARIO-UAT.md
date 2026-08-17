# Skenario UAT (User Acceptance Testing) Sistem MARLIN

## Pengantar

User Acceptance Testing, atau yang biasa disingkat UAT, adalah tahap pengujian di mana sebuah sistem diuji langsung oleh atau atas nama pengguna sesungguhnya, dengan tujuan memastikan sistem tersebut benar-benar memenuhi kebutuhan nyata mereka, bukan sekadar lolos dari pengujian teknis internal seperti unit test atau feature test yang ditulis oleh pengembang. Dokumen ini menyusun skenario UAT untuk dua peran pengguna di dalam Sistem MARLIN, yaitu **Admin** dan **Petugas Lapangan**, disusun mengikuti alur fitur yang benar-benar berjalan di dalam sistem, bukan berdasarkan rencana atau spekulasi.

Sumber kebenaran yang dipakai untuk menyusun setiap skenario di dalam dokumen ini adalah [FITUR.md](FITUR.md), [ALUR-BISNIS.md](ALUR-BISNIS.md), dan [DAFTAR-AKTIVITAS.md](DAFTAR-AKTIVITAS.md). Kolom **Kriteria Penerimaan** pada setiap baris sengaja ditulis sebagai sebuah kondisi lolos atau gagal yang bisa langsung dicentang oleh penguji saat menjalankan skenario tersebut, bukan sekadar penjelasan umum yang masih memerlukan interpretasi lebih lanjut. Pendekatan ini penting supaya hasil pengujian bisa dicatat secara objektif dan konsisten, terlepas dari siapa penguji yang menjalankannya.

Setiap skenario diberi kode unik, `UAT-ADM-xx` untuk skenario yang berkaitan dengan peran Admin, dan `UAT-USR-xx` untuk skenario yang berkaitan dengan peran Petugas. Kode ini dipakai secara konsisten di seluruh dokumen supaya bisa langsung dirujuk balik dari laporan bug atau catatan pengujian mana pun yang disusun kemudian.

---

## Daftar Isi

- [Skenario UAT: Admin](#skenario-uat-admin)
  - [Autentikasi & Akun](#autentikasi--akun-admin)
  - [Dashboard](#dashboard-admin)
  - [Pengerjaan (SPK)](#pengerjaan-spk-admin)
  - [Validasi Laporan](#validasi-laporan-admin)
  - [Temuan Lapangan](#temuan-lapangan-admin)
  - [Rambu](#rambu-admin)
  - [Riwayat & Laporan](#riwayat--laporan-admin)
  - [Lainnya (Manajemen Petugas & Log Error)](#lainnya-manajemen-petugas--log-error-admin)
- [Skenario UAT: Petugas Lapangan](#skenario-uat-petugas-lapangan)
  - [Autentikasi & Akun](#autentikasi--akun-petugas)
  - [Pengerjaan (SPK)](#pengerjaan-spk-petugas)
  - [Eksekusi Lapangan](#eksekusi-lapangan-petugas)
  - [Rambu](#rambu-petugas)
  - [Laporan & Riwayat](#laporan--riwayat-petugas)
- [Skenario UAT: Fitur Bersama](#skenario-uat-fitur-bersama)

---

## Skenario UAT: Admin

### Autentikasi & Akun (Admin)

Kelompok skenario ini menguji jalur masuk seorang admin ke dalam sistem, mencakup kasus normal maupun kasus penolakan yang seharusnya terjadi.

| Kode | Fitur | Skenario | Kriteria Penerimaan |
|---|---|---|---|
| UAT-ADM-01 | Login | Admin login dengan NIP dan kata sandi yang benar | Berhasil masuk dan diarahkan ke Dashboard Admin |
| UAT-ADM-02 | Login | Admin login dengan kata sandi yang salah | Ditolak dengan pesan "NIP atau kata sandi yang Anda masukkan salah", tidak masuk ke sistem |
| UAT-ADM-03 | Login | Percobaan login gagal enam kali berturut-turut dalam satu menit dengan NIP dan IP yang sama | Percobaan keenam diblokir dengan pesan "terlalu banyak percobaan", bukan pesan kata sandi salah |
| UAT-ADM-04 | Login | Akun admin yang sudah dinonaktifkan mencoba login | Ditolak dengan pesan "Akun ini telah dinonaktifkan...", tidak masuk ke sistem |
| UAT-ADM-05 | 2FA | Admin dengan 2FA aktif login lalu diminta kode verifikasi | Tidak bisa masuk ke dashboard sebelum kode 2FA yang benar dimasukkan |

### Dashboard (Admin)

| Kode | Fitur | Skenario | Kriteria Penerimaan |
|---|---|---|---|
| UAT-ADM-06 | Dashboard | Membuka Dashboard Admin saat ada SPK aktif, rambu rusak, dan laporan yang menunggu validasi | Angka ringkasan (SPK aktif, rambu rusak, menunggu validasi) sesuai dengan data sesungguhnya di dalam basis data |
| UAT-ADM-07 | Dashboard, Widget Peta | Mengubah filter jenis rambu, tingkat, atau tanggal pada widget peta dashboard | Pin yang tampil di peta berubah sesuai filter, tanpa memuat ulang halaman |
| UAT-ADM-08 | Dashboard, Widget Peta | Mengklik tombol "Unduh PDF" pada widget peta | Berkas PDF sebaran rambu terunduh, berisi cuplikan peta sesuai filter yang aktif dan tabel analitik |

### Pengerjaan (SPK) (Admin)

Kelompok skenario ini adalah yang paling banyak jumlahnya, mencerminkan betapa sentralnya siklus SPK di dalam keseluruhan sistem.

| Kode | Fitur | Skenario | Kriteria Penerimaan |
|---|---|---|---|
| UAT-ADM-09 | Buat Surat | Mengisi formulir SPK lengkap dengan satu baris rambu **Pemasangan Baru** lalu menyimpannya | SPK tersimpan berstatus Aktif, seluruh petugas aktif menerima notifikasi "SPK Baru Tersedia" |
| UAT-ADM-10 | Buat Surat | Menambahkan baris kedua bertipe **Perbaikan** (memilih rambu yang sudah terdaftar) pada SPK yang sama | Kedua baris tersimpan dengan jenis pekerjaan masing-masing; kartu SPK di Daftar Surat menampilkan lencana **"Pemasangan & Perbaikan"** |
| UAT-ADM-11 | Buat Surat | Mengisi Deadline dengan tanggal hari ini atau tanggal yang sudah lewat | Ditolak dengan pesan "Deadline harus setelah hari ini", SPK tidak tersimpan |
| UAT-ADM-12 | Buat Surat | Mengisi field RT dengan huruf (misalnya "5A") | Ditolak dengan pesan "RT hanya boleh berisi angka" sebelum tombol Simpan Surat ditekan |
| UAT-ADM-13 | Buat Surat | Menandai SPK sebagai Prioritas | Urgensi otomatis menjadi Tinggi, dan SPK aktif non-prioritas lain tenggat waktunya ikut mundur (bersifat maksimal, tidak akumulatif) |
| UAT-ADM-14 | Buat Surat dari Temuan | Mengklik "Buat SPK" dari sebuah Temuan Lapangan | Baris rambu otomatis terisi (rambu dan foto dari temuan), jenis pekerjaan baris itu otomatis menjadi Perbaikan |
| UAT-ADM-15 | Edit Surat | Mengubah data SPK yang masih berstatus Aktif | Perubahan tersimpan dan tercatat di Audit Log (`spk_diedit`) |
| UAT-ADM-16 | Edit Surat | Mencoba mengakses halaman Edit untuk SPK yang sudah Selesai atau Dibatalkan | Ditolak (kode kesalahan 403), tidak bisa membuka formulir edit |
| UAT-ADM-17 | Edit Surat | Mengubah jenis pekerjaan satu baris rambu yang statusnya masih Belum (misalnya dari Pasang Baru menjadi Perbaikan) | Perubahan tersimpan, baris tersebut menampilkan jenis pekerjaan yang baru |
| UAT-ADM-18 | Edit Surat | Membuka halaman Edit untuk SPK yang salah satu barisnya sudah berstatus Menunggu Validasi atau Selesai | Baris tersebut ditampilkan sebagai ringkasan baca-saja, bukan formulir yang bisa diubah, dan tombol Batalkan untuk baris itu tidak muncul |
| UAT-ADM-19 | Batalkan SPK | Membatalkan seluruh SPK yang masih Aktif | Status berubah menjadi Dibatalkan (bukan terhapus), semua rambu yang belum selesai ikut menjadi Batal, tim yang sudah bergabung menerima notifikasi |
| UAT-ADM-20 | Batalkan Satu Rambu | Membatalkan satu baris rambu saja dari Edit Surat, mengisi alasan | Hanya rambu itu berstatus Batal, rambu lain di SPK yang sama tidak terganggu, alasan tampil pada kartu rambu |
| UAT-ADM-21 | Hapus Rambu | Menghapus permanen baris rambu yang masih berstatus Belum dan belum ada laporan atau kendala | Baris rambu_pasang terhapus dari surat, data rambu fisiknya tetap ada di dalam sistem |

### Validasi Laporan (Admin)

| Kode | Fitur | Skenario | Kriteria Penerimaan |
|---|---|---|---|
| UAT-ADM-22 | Antrean Validasi | Membuka halaman Validasi Laporan | Hanya menampilkan SPK yang sudah mengajukan Laporan Akhir |
| UAT-ADM-23 | Validasi, Terima | Menerima sebuah laporan pengerjaan yang diajukan petugas | Status rambu berubah menjadi Selesai; untuk Pasang Baru `sudah_terpasang` menjadi benar, untuk Perbaikan `kondisi_terkini` menjadi baik |
| UAT-ADM-24 | Validasi, Tolak | Menolak sebuah laporan tanpa mengisi catatan penolakan | Ditolak oleh sistem, wajib mengisi catatan terlebih dahulu sebelum bisa dikirim |
| UAT-ADM-25 | Validasi, Tolak | Menolak laporan dengan catatan penolakan yang sudah terisi | Status rambu berubah menjadi Revisi, petugas menerima notifikasi berisi catatan tersebut |
| UAT-ADM-26 | Validasi, Kendala | Mencoba menandai rambu berstatus Tertunda (kendala) sebagai diterima | Kartu kendala sama sekali tidak bisa diklik atau dicentang; begitu diproses, otomatis masuk ke jalur revisi |
| UAT-ADM-27 | Validasi, Perpanjang Deadline | Saat menolak laporan, mencentang "Beri kelonggaran, perpanjang deadline" dan mengisi tanggal baru | Tenggat waktu SPK berubah, urgensi dihitung ulang, seluruh tim menerima notifikasi perubahan tenggat waktu |
| UAT-ADM-28 | Validasi, SPK Selesai Otomatis | Menerima atau menolak-lalu-menerima seluruh rambu dalam satu SPK hingga semuanya Selesai atau Batal | Status SPK otomatis berubah menjadi Selesai, `selesai_pada` tercatat |
| UAT-ADM-29 | Validasi, Data Sudah Berubah | Sebuah rambu yang statusnya sudah berubah di sesi lain (misalnya sudah divalidasi admin lain) tetap tercentang pada sesi ini, lalu diproses | Sistem menampilkan pesan kesalahan yang jelas, bukan pesan sukses; daftar rambu yang benar-benar masih perlu diproses dimuat ulang |

### Temuan Lapangan (Admin)

| Kode | Fitur | Skenario | Kriteria Penerimaan |
|---|---|---|---|
| UAT-ADM-30 | Temuan Lapangan | Membuka antrean Temuan Lapangan | Hanya menampilkan temuan berstatus "baru" (belum ditindaklanjuti) |
| UAT-ADM-31 | Tindak Lanjut, Buat SPK | Membuat SPK perbaikan dari sebuah temuan | SPK baru tercipta, status temuan berubah menjadi "sudah dibuatkan SPK", foto temuan otomatis menjadi foto survei rambu di SPK baru |
| UAT-ADM-32 | Tindak Lanjut, Tolak | Menolak sebuah temuan | Status temuan berubah menjadi Ditolak, petugas pelapor menerima notifikasi |

### Rambu (Admin)

| Kode | Fitur | Skenario | Kriteria Penerimaan |
|---|---|---|---|
| UAT-ADM-33 | Jenis Rambu | Menambahkan jenis rambu baru dengan nama mengandung angka (misalnya "Rambu 123") | Ditolak, nama jenis rambu hanya boleh berisi huruf dan spasi |
| UAT-ADM-34 | Jenis Rambu | Menambah, mengubah, atau menghapus jenis rambu dengan data yang valid | Perubahan tersimpan dan langsung terlihat pada daftar jenis rambu; penghapusan menampilkan modal konfirmasi bergaya khas aplikasi, bukan dialog bawaan peramban |
| UAT-ADM-35 | Daftar Rambu | Memfilter daftar rambu berdasarkan kondisi (baik/rusak) dan wilayah | Hasil daftar sesuai dengan filter yang dipilih |

### Riwayat & Laporan (Admin)

| Kode | Fitur | Skenario | Kriteria Penerimaan |
|---|---|---|---|
| UAT-ADM-36 | Riwayat SPK | Membuka Riwayat SPK, memfilter status Selesai atau Dibatalkan | Hanya SPK dengan status sesuai filter yang tampil, SPK Aktif tidak muncul di sini |
| UAT-ADM-37 | Riwayat SPK | Memfilter Riwayat SPK dengan rentang tanggal "Dari Tanggal" sampai "Sampai Tanggal" | Hanya SPK yang terakhir diperbarui dalam rentang tersebut yang tampil |
| UAT-ADM-38 | Riwayat SPK | Membuka Detail Surat untuk SPK berstatus Selesai | Menampilkan Durasi Pengerjaan dan Selisih dari Deadline (lencana hijau apabila lebih cepat, merah apabila terlambat) |
| UAT-ADM-39 | Riwayat Aktivitas | Membuka Riwayat Aktivitas (Audit Log) sebagai admin | Menampilkan aksi bisnis dari **seluruh pengguna**, bukan hanya admin yang sedang login |
| UAT-ADM-40 | Riwayat Aktivitas | Memfilter Riwayat Aktivitas berdasarkan pengguna tertentu dan rentang tanggal | Hanya menampilkan aksi milik pengguna yang dipilih dalam rentang tanggal tersebut |
| UAT-ADM-41 | Laporan Bulanan | Mengatur filter rentang tanggal, jenis rambu, atau status lalu mengunduh PDF | Berkas PDF terunduh berisi rekap sesuai filter yang diterapkan |
| UAT-ADM-42 | Laporan Rambu | Mengatur filter lalu mengunduh PDF riwayat pekerjaan per rambu | Berkas PDF terunduh, angka konsisten dengan Laporan Bulanan untuk filter yang sama |

### Lainnya (Manajemen Petugas & Log Error) (Admin)

| Kode | Fitur | Skenario | Kriteria Penerimaan |
|---|---|---|---|
| UAT-ADM-43 | Manajemen Petugas | Menambahkan akun petugas baru dengan data lengkap | Akun baru langsung bisa dipakai untuk login |
| UAT-ADM-44 | Manajemen Petugas | Menonaktifkan akun petugas yang sedang tidak login | Akun tersebut gagal login pada percobaan berikutnya dengan pesan akun dinonaktifkan |
| UAT-ADM-45 | Manajemen Petugas | Mengisi Nama atau Nama Panggilan dengan angka | Ditolak, field tersebut hanya boleh berisi huruf dan spasi |
| UAT-ADM-46 | Log Error Sistem | Terjadi exception tak tertangani (level 500) di dalam aplikasi | Baris baru muncul secara otomatis di System Error Log tanpa aksi manual apa pun dari admin |

---

## Skenario UAT: Petugas Lapangan

### Autentikasi & Akun (Petugas)

| Kode | Fitur | Skenario | Kriteria Penerimaan |
|---|---|---|---|
| UAT-USR-01 | Login | Petugas login dengan NIP dan kata sandi yang benar | Berhasil masuk, diarahkan ke Daftar Surat Aktif |
| UAT-USR-02 | Reset Kata Sandi | Membuka wizard reset kata sandi di halaman login, memverifikasi NIP dan kata sandi lama, lalu menetapkan kata sandi baru | Kata sandi berhasil diganti, bisa login dengan kata sandi baru |
| UAT-USR-03 | Reset Kata Sandi | Mencoba memverifikasi identitas enam kali dengan kata sandi lama yang salah dalam satu menit | Percobaan keenam diblokir untuk sementara waktu |

### Pengerjaan (SPK) (Petugas)

| Kode | Fitur | Skenario | Kriteria Penerimaan |
|---|---|---|---|
| UAT-USR-04 | Daftar Surat Aktif | Membuka dashboard petugas | Menampilkan **semua** SPK berstatus Aktif, bukan hanya yang sudah diikuti tim sendiri |
| UAT-USR-05 | Gabung Tim | Mendaftarkan diri sebagai perwakilan pada SPK yang belum ada timnya, sekaligus memilih rekan setim | Tim tercatat, kartu SPK menampilkan lencana "Sudah Bergabung" |
| UAT-USR-06 | Gabung Tim | Dua petugas mencoba mendaftarkan tim pada SPK yang sama hampir bersamaan | Petugas kedua menerima pesan peringatan "sudah didaftarkan orang lain" dan halaman dimuat ulang, tidak membuat tim ganda |
| UAT-USR-07 | Kelola Tim | Sebagai perwakilan, menambahkan anggota baru ke tim yang sudah ada | Anggota baru tercatat, tampil pada daftar Tim Bertugas |
| UAT-USR-08 | Kelola Tim | Sebagai perwakilan, menghapus seorang anggota (bukan perwakilan) dari tim | Anggota terhapus dari tim, dia menerima notifikasi "Dikeluarkan dari Tim" |
| UAT-USR-09 | Kelola Tim | Mencoba menambah atau menghapus anggota tim pada SPK yang **sudah Selesai** | Tombol tambah dan hapus anggota tidak muncul, aksi ditolak walau dicoba secara langsung |
| UAT-USR-10 | Surat Pengantar | Mengklik tombol unduh Surat Pengantar pada SPK yang **belum diikuti** timnya | Muncul pesan peringatan "Gabung dulu ke tim surat ini...", tidak diarahkan ke halaman kesalahan atau forbidden |
| UAT-USR-11 | Surat Pengantar | Mengunduh Surat Pengantar pada SPK yang sudah diikuti tim | Berkas PDF terunduh, berisi data surat, daftar rambu, dan tim |

### Eksekusi Lapangan (Petugas)

| Kode | Fitur | Skenario | Kriteria Penerimaan |
|---|---|---|---|
| UAT-USR-12 | Laporan Pengerjaan | Sebagai perwakilan, mengirim Form Laporan Pengerjaan tanpa mengisi foto sesudah | Ditolak dengan pesan kesalahan yang jelas, laporan tidak terkirim |
| UAT-USR-13 | Laporan Pengerjaan | Mengambil lokasi GPS lewat tombol "Ambil Lokasi Sekarang" saat izin lokasi peramban **ditolak** | Muncul pesan peringatan berwarna merah yang menjelaskan izin ditolak dan cara mengaktifkannya, bukan tombol yang diam saja |
| UAT-USR-14 | Laporan Pengerjaan | Mengambil lokasi GPS dengan izin diberikan, lalu mengirim laporan lengkap (foto, GPS, catatan) | Koordinat terisi otomatis, laporan terkirim, status rambu berubah menjadi Menunggu Validasi |
| UAT-USR-15 | Form Kendala | Sebagai perwakilan, mengirim Form Kendala dengan foto dan alasan terisi | Status rambu berubah menjadi Tertunda, alasan kendala langsung terlihat di Detail SPK |
| UAT-USR-16 | Tukar Kendala/Laporan | Mengubah rambu yang berstatus Tertunda (ada kendala) menjadi mengisi Laporan Pengerjaan | Kendala lama terhapus, laporan baru tercatat, status berubah menjadi Menunggu Validasi |
| UAT-USR-17 | Non-Perwakilan | Anggota tim yang **bukan** perwakilan mencoba mengisi Laporan Pengerjaan atau Kendala | Aksi ditolak sistem, tidak ada laporan atau kendala yang tersimpan |
| UAT-USR-18 | Ajukan Laporan Akhir | Mencoba mengajukan Laporan Akhir saat masih ada rambu berstatus Belum atau Revisi | Tombol "Ajukan Laporan Akhir" tidak muncul atau tidak bisa diklik |
| UAT-USR-19 | Ajukan Laporan Akhir | Mengajukan Laporan Akhir setelah semua rambu berstatus Tertunda, Menunggu Validasi, atau Selesai | SPK masuk antrean Validasi Laporan admin, admin pembuat SPK menerima notifikasi |

### Rambu (Petugas)

| Kode | Fitur | Skenario | Kriteria Penerimaan |
|---|---|---|---|
| UAT-USR-20 | Peta Rambu | Membuka Peta Rambu dan mengklik salah satu pin | Kartu informasi (foto, jenis rambu, lokasi, status) terbuka, warna pin sesuai status terkini |
| UAT-USR-21 | Lapor Temuan dari Peta | Mengklik "Lapor Temuan Kondisi" dari kartu informasi pin, mengisi foto yang diwajibkan | Kondisi rambu berubah menjadi Rusak, pin berubah warna, seluruh admin menerima notifikasi |
| UAT-USR-22 | Daftar/Jenis Rambu | Membuka halaman Daftar Rambu atau Jenis Rambu sebagai petugas | Data tampil, tidak ada tombol tambah, ubah, atau hapus (bersifat baca-saja) |

### Laporan & Riwayat (Petugas)

| Kode | Fitur | Skenario | Kriteria Penerimaan |
|---|---|---|---|
| UAT-USR-23 | Riwayat Pekerjaan Saya | Membuka Riwayat Pekerjaan Saya tanpa mengisi filter rentang tanggal apa pun | Seluruh SPK yang pernah dikerjakan tim sendiri ditampilkan, tidak dibatasi ke periode tertentu secara diam-diam |
| UAT-USR-24 | Riwayat Pekerjaan Saya | Mengisi filter "Dari Tanggal" dan "Sampai Tanggal" pada Riwayat Pekerjaan Saya | Hanya SPK yang diikuti dalam rentang tanggal tersebut yang ditampilkan |
| UAT-USR-25 | Riwayat Aktivitas | Membuka Riwayat Aktivitas sebagai petugas | Hanya menampilkan aktivitas yang dilakukan **oleh dirinya sendiri**, bukan aktivitas admin atau rekan setim |

---

## Skenario UAT: Fitur Bersama

Skenario di bawah ini berlaku untuk kedua peran (Admin dan Petugas), dan sebaiknya diuji sekali per peran apabila ada kemungkinan perilakunya berbeda antara keduanya.

| Kode | Fitur | Skenario | Kriteria Penerimaan |
|---|---|---|---|
| UAT-BSM-01 | Notifikasi | Terjadi peristiwa yang relevan (misalnya SPK baru, hasil validasi) | Lencana merah pada ikon lonceng bertambah, notifikasi baru muncul pada daftar |
| UAT-BSM-02 | Notifikasi | Mengklik kartu notifikasi yang memiliki tujuan halaman | Halaman terkait terbuka dan notifikasi otomatis ditandai terbaca |
| UAT-BSM-03 | Telegram | Menghubungkan akun ke bot Telegram lewat token sekali pakai | Kolom `telegram_chat_id` tersimpan, notifikasi berikutnya juga terkirim sebagai pesan Telegram |
| UAT-BSM-04 | Telegram | Memutuskan hubungan akun Telegram yang sudah terhubung | Notifikasi Telegram berhenti terkirim, in-app tetap berjalan normal |
| UAT-BSM-05 | Profil | Mengubah nama, nama panggilan, atau foto profil sendiri | Perubahan tersimpan dan langsung terlihat pada header ("Halo, ...") |
| UAT-BSM-06 | Keamanan | Membuka Settings menuju Keamanan tanpa konfirmasi ulang kata sandi | Diminta konfirmasi kata sandi terlebih dahulu sebelum halaman bisa diakses, walaupun sesi login masih aktif |
| UAT-BSM-07 | Keamanan | Mengganti kata sandi lewat Settings menuju Keamanan | Kata sandi baru aktif, bisa dipakai untuk login berikutnya |
| UAT-BSM-08 | Logout | Mengklik Logout | Sesi berakhir, diarahkan ke halaman login, akses ke halaman terproteksi ditolak setelahnya |

---

## Catatan Penggunaan Dokumen Ini

Kolom **Kriteria Penerimaan** ditulis sebagai kondisi tunggal yang bisa dicentang lolos atau gagal. Apabila sebuah skenario membutuhkan beberapa kondisi sekaligus untuk dianggap benar-benar berhasil, misalnya pada UAT-ADM-19, seluruh kondisi tersebut harus terpenuhi secara bersamaan agar skenario itu dinyatakan **Lolos**.

Skenario yang menyentuh perubahan-perubahan terbaru, seperti jenis pekerjaan per baris rambu, penguncian baris rambu yang sudah divalidasi, filter rentang tanggal yang menggantikan filter bulanan, dan robustnya proses validasi terhadap data yang sudah berubah, sengaja dimasukkan ke dalam dokumen ini karena titik-titik inilah yang paling baru diverifikasi dan paling rawan mengalami regresi apabila ada perubahan kode di kemudian hari.

Dokumen ini berfokus pada skenario fungsional. Untuk detail teknis yang lebih mendalam di balik setiap aturan bisnis yang diuji, silakan telusuri [ALUR-BISNIS.md](ALUR-BISNIS.md). Untuk daftar lengkap setiap interaksi, termasuk yang tidak dimasukkan ke dalam UAT karena risikonya dianggap rendah, silakan lihat [DAFTAR-AKTIVITAS.md](DAFTAR-AKTIVITAS.md).
