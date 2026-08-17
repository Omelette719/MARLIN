# Daftar Aktivitas / Interaksi Sistem MARLIN

## Pengantar

Dokumen ini berfungsi sebagai katalog lengkap seluruh aktivitas dan interaksi yang terjadi di dalam Sistem MARLIN, dikelompokkan berdasarkan pasangan aktor yang terlibat di dalamnya: interaksi antara **Petugas dengan Sistem**, antara **Admin dengan Sistem**, dampak-dampak yang saling terkait antara **Admin dengan Petugas** (walaupun secara teknis kedua interaksi ini selalu dimediasi oleh sistem, bukan komunikasi langsung), dan terakhir sebuah kategori **Lainnya** yang mencakup proses-proses otomatis, integrasi dengan layanan eksternal, serta interaksi yang terjadi sebelum seorang pengguna berhasil login.

Dokumen ini disiapkan sebagai bahan mentah untuk menyusun diagram aktivitas (activity diagram), sehingga setiap baris di dalamnya sengaja ditulis sebagai satu aktivitas atau proses bisnis yang bisa langsung diturunkan menjadi elemen-elemen diagram alur: titik mulai, titik keputusan, aksi, dan titik selesai. Untuk detail teknis yang lebih mendalam di balik setiap aktivitas, mencakup aturan bisnis, urutan status, dan alasan di balik setiap keputusan desainnya, silakan telusuri [ALUR-BISNIS.md](ALUR-BISNIS.md), [FITUR.md](FITUR.md), dan [HALAMAN.md](HALAMAN.md), yang membahas ketiganya secara jauh lebih terperinci. Seluruh isi dokumen ini sudah diverifikasi langsung terhadap kode sumber yang sesungguhnya berjalan.

---

## Daftar Isi

- [A. Interaksi Petugas dengan Sistem](#a-interaksi-petugas-dengan-sistem)
- [B. Interaksi Admin dengan Sistem](#b-interaksi-admin-dengan-sistem)
- [C. Interaksi Admin dengan Petugas](#c-interaksi-admin-dengan-petugas)
- [D. Interaksi Lainnya](#d-interaksi-lainnya)

---

## A. Interaksi Petugas dengan Sistem

Bagian ini mencatat setiap aktivitas yang dilakukan petugas lapangan secara langsung terhadap sistem, mulai dari mereka pertama kali login sampai berbagai aksi yang bisa mereka lakukan selama menggunakan aplikasi.

| Kode | Aktivitas | Prasyarat / Pemicu | Hasil |
|---|---|---|---|
| US-01 | Login (NIP + kata sandi) | Memiliki akun yang aktif | Masuk ke Dashboard Petugas; apabila 2FA aktif, diminta memasukkan kode verifikasi terlebih dahulu |
| US-02 | Reset kata sandi lewat wizard di halaman login | Lupa kata sandi, mengetahui NIP dan kata sandi lama | Kata sandi baru tersimpan; dibatasi lima percobaan per menit per kombinasi NIP dan IP |
| US-03 | Melihat Dashboard (Daftar Surat Aktif) | Sudah login | Melihat semua SPK aktif (bukan hanya yang diikuti) untuk mencari pekerjaan baru |
| US-04 | Melihat Detail SPK | Mengklik salah satu SPK aktif | Melihat detail surat, tim, dan daftar rambu, sebelum memutuskan untuk bergabung |
| US-05 | Mendaftarkan tim (bergabung ke SPK sebagai perwakilan) | Belum ada tim yang terdaftar pada SPK tersebut | Perwakilan beserta rekan setim tercatat sebagai tim SPK; sebuah modal konfirmasi ditampilkan karena aksi ini tidak bisa dibatalkan lewat sistem; apabila ada petugas lain yang lebih dulu mendaftar (kondisi balapan), muncul pesan peringatan (toast) dan halaman dimuat ulang |
| US-06 | Menambah anggota tim di kemudian hari | Sudah menjadi perwakilan tim pada SPK tersebut | Anggota baru tercatat; sebuah modal konfirmasi ditampilkan; wajib memilih minimal satu anggota, muncul pesan peringatan apabila formulir dikirim kosong atau seluruh anggota yang dipilih ternyata sudah ada di dalam tim |
| US-07 | Mengisi Form Kendala | Sebagai perwakilan tim, rambu berstatus Belum atau Revisi | Status rambu berubah menjadi Tertunda; alasan kendala langsung terlihat di Detail SPK (baik versi petugas maupun admin), admin baru menerima notifikasi nanti sekali saja saat Laporan Akhir diajukan (lihat US-11), bukan per kendala |
| US-08 | Mengedit atau menukar Kendala menjadi Laporan Pengerjaan | Rambu berstatus Tertunda, SPK belum mengajukan Laporan Akhir | Kendala lama terhapus, laporan pengerjaan baru tercatat, status berubah menjadi Menunggu Validasi |
| US-09 | Mengisi Form Laporan Pengerjaan | Sebagai perwakilan tim, rambu berstatus Belum atau Revisi | Status rambu berubah menjadi Menunggu Validasi; tidak ada notifikasi per rambu yang dikirimkan kepada admin (lihat US-11) |
| US-10 | Mengedit atau menukar Laporan Pengerjaan menjadi Kendala | Rambu berstatus Menunggu Validasi, SPK belum mengajukan Laporan Akhir | Laporan lama terhapus, kendala baru tercatat, status berubah menjadi Tertunda |
| US-11 | Mengajukan Laporan Akhir | Sebagai perwakilan tim, seluruh rambu SPK sudah berstatus Tertunda/Menunggu Validasi/Selesai (minimal satu yang baru) | SPK masuk antrean Validasi Pengerjaan milik admin |
| US-12 | Melaporkan Temuan Kondisi | Siapa saja (tidak harus perwakilan), melihat rambu rusak di lapangan | Kolom `kondisi_terkini` rambu berubah menjadi Rusak, pin di peta berubah warna, seluruh admin menerima notifikasi |
| US-13 | Melihat Riwayat Pekerjaan Saya | Sudah login | Daftar SPK yang pernah dikerjakan tim, dilengkapi filter rentang tanggal "Dari Tanggal" sampai "Sampai Tanggal"; apabila keduanya dikosongkan, seluruh riwayat ditampilkan |
| US-14 | Melihat SPK Sedang Dikerjakan | Sudah login | Sebuah subset dari dashboard, hanya menampilkan SPK yang timnya sudah bergabung |
| US-15 | Mengunduh Surat Pengantar (PDF) | Anggota tim SPK tersebut (atau admin) | Berkas PDF surat pengantar terunduh |
| US-16 | Melihat dan memfilter Peta Interaktif | Sudah login | Pin rambu tampil sesuai filter (jenis, tingkat, tanggal); mengklik pin membuka kartu informasi |
| US-17 | Melaporkan Temuan langsung dari kartu pin peta | Mengklik tombol di dalam kartu informasi pin | Sama seperti US-12, sebuah jalan pintas dari peta |
| US-18 | Melihat Detail Rambu | Mengklik rambu di peta atau daftar | Riwayat foto, kondisi, dan pekerjaan rambu tersebut |
| US-19 | Melihat Jenis Rambu dan Daftar Rambu | Sudah login | Referensi master data, bersifat baca-saja bagi petugas |
| US-20 | Melihat dan membaca Notifikasi in-app | Mengklik ikon lonceng di header | Lencana belum-dibaca berkurang; notifikasi yang memiliki tujuan halaman langsung membuka halaman terkait |
| US-21 | Menghubungkan akun Telegram | Membuka Settings menuju Telegram, menekan Start pada bot | Kolom `telegram_chat_id` tersimpan; notifikasi berikutnya juga terkirim ke Telegram |
| US-22 | Memutuskan akun Telegram | Sudah terhubung sebelumnya | Kolom `telegram_chat_id` dikosongkan, notifikasi Telegram berhenti terkirim |
| US-23 | Melihat Riwayat Aktivitas (Audit Log) | Sudah login | Hanya melihat aksi yang dilakukannya sendiri |
| US-24 | Mengubah Profil (nama, foto) | Sudah login | Data profil diperbarui |
| US-25 | Mengganti Kata Sandi / mengelola 2FA | Konfirmasi ulang kata sandi | Kata sandi baru aktif, atau 2FA diaktifkan/dinonaktifkan |
| US-26 | Logout | Sudah login | Sesi berakhir, kembali ke halaman login |
| US-27 | Menghapus anggota tim (bukan perwakilan) | Perwakilan tim SPK tersebut, konfirmasi modal | Anggota terkait dilepas dari tim SPK ini, menerima notifikasi; baris perwakilan sendiri tidak bisa dihapus lewat aksi ini; apabila anggotanya sudah lebih dulu dihapus di sesi lain, muncul pesan peringatan alih-alih diam saja |

## B. Interaksi Admin dengan Sistem

Bagian ini mencatat setiap aktivitas yang dilakukan admin secara langsung terhadap sistem, mencakup pengelolaan master data, pemrosesan laporan, dan penghasilan berbagai dokumen tercetak.

| Kode | Aktivitas | Prasyarat / Pemicu | Hasil |
|---|---|---|---|
| AS-01 | Login (NIP + kata sandi) beserta 2FA | Memiliki akun admin yang aktif | Masuk ke Dashboard Admin |
| AS-02 | Melihat Dashboard Admin | Sudah login | Ringkasan SPK aktif, rambu rusak, laporan menunggu validasi, dan widget peta ringkas |
| AS-03 | Membuat Surat (SPK) | Mengisi formulir: jenis, alamat, tenggat waktu, daftar rambu (bisa banyak baris sekaligus) | SPK baru (berstatus Aktif), urgensi terhitung otomatis; seluruh petugas aktif menerima notifikasi |
| AS-04 | Mengedit Surat (SPK) | SPK masih berstatus Aktif | Data header dan/atau daftar rambu berubah, tercatat di Audit Log; khusus baris rambu, hanya baris yang statusnya masih Belum/Urgent/Revisi yang bisa diubah, baris lainnya ditampilkan sebagai ringkasan baca-saja |
| AS-05 | Membatalkan SPK (seluruh surat) | SPK masih Aktif, konfirmasi modal | Status berubah menjadi Dibatalkan, seluruh rambu yang belum selesai ikut menjadi Batal, tim yang sudah bergabung menerima notifikasi |
| AS-06 | Membatalkan satu rambu dalam SPK | Mengisi alasan wajib lewat modal, hanya untuk rambu berstatus Belum/Urgent/Revisi | Rambu itu berubah menjadi Batal, rambu lain di SPK yang sama tidak terganggu |
| AS-07 | Menghapus permanen satu baris rambu_pasang | Status Belum atau Batal, belum ada kendala atau laporan | Baris rambu_pasang terhapus; baris rambu (aset fisik) tetap ada |
| AS-08 | Melihat Daftar Surat dan Riwayat SPK | Sudah login | Daftar SPK aktif, dan arsip yang Selesai atau Dibatalkan (dilengkapi filter status, jenis, rentang tanggal, dan pencarian) |
| AS-09 | Memproses Validasi Pengerjaan (menerima/menolak per rambu) | SPK sudah mengajukan Laporan Akhir | Rambu yang diterima berubah menjadi Selesai (`rambu.sudah_terpasang` atau `kondisi_terkini` ikut berubah), atau yang ditolak berubah menjadi Revisi dengan catatan wajib |
| AS-09b | Memperpanjang tenggat waktu SPK sambil menolak validasi (opsional) | Pada Form Penolakan yang sama dengan AS-09, mencentang "beri kelonggaran" | Deadline dan deadline_asli SPK berubah, urgensi dihitung ulang, tercatat di Audit Log, tim menerima notifikasi; satu transaksi dengan penolakan rambunya |
| AS-10 | Melihat antrean Temuan Lapangan | Sudah login | Daftar laporan kondisi rusak yang belum ditindaklanjuti |
| AS-11 | Menindaklanjuti Temuan: membuat SPK perbaikan dari temuan | Memilih temuan, mengisi formulir SPK | SPK perbaikan baru tercipta, status temuan berubah menjadi "sudah dibuatkan SPK"; foto yang sudah ada pada laporan temuan ikut terbawa sebagai foto rambu di SPK baru |
| AS-12 | Menindaklanjuti Temuan: menolak temuan | Memilih temuan | Status temuan berubah menjadi Ditolak, pelapor menerima notifikasi |
| AS-13 | Menghasilkan dan mengunduh Laporan Bulanan (PDF) | Mengatur filter rentang tanggal, jenis rambu, atau status | Berkas PDF rekap terunduh |
| AS-14 | Menghasilkan dan mengunduh Laporan Rambu (PDF) | Mengatur filter rentang tanggal, jenis rambu, atau status | Berkas PDF riwayat pekerjaan rambu terunduh |
| AS-15 | Mengelola Jenis Rambu (tambah/ubah/hapus) | Sudah login sebagai admin | Master data jenis rambu berubah; penghapusan memakai modal konfirmasi bergaya khas aplikasi ini, bukan dialog bawaan peramban |
| AS-16 | Melihat Kelola Rambu | Sudah login | Daftar semua rambu, dilengkapi filter kondisi dan wilayah |
| AS-17 | Menambah akun petugas atau admin | Mengisi formulir data diri beserta kata sandi awal | Akun baru aktif, bisa langsung digunakan untuk login |
| AS-18 | Mengedit akun (data diri, aktifkan/nonaktifkan) | Memilih akun dari Kelola Pengguna | Data akun berubah; akun yang nonaktif tidak bisa login lagi |
| AS-19 | Melihat Audit Log (seluruh sistem) | Sudah login sebagai admin | Melihat semua aksi bisnis penting lintas pengguna, dilengkapi filter jenis aksi, filter pengguna, dan filter rentang tanggal |
| AS-20 | Melihat System Error Log | Sudah login sebagai admin | Melihat exception tak terduga (level 500 ke atas) yang tertangkap secara otomatis |
| AS-21 | Melihat dan memfilter Peta, mengunduh PDF sebaran rambu | Sudah login, mengatur filter pada widget peta | Cuplikan gambar peta beserta tabel analitik terangkum menjadi PDF |
| AS-22 | Mengelola Profil / Kata Sandi / 2FA / Telegram | Sama seperti versi petugas (US-21, US-22, US-24, US-25) | Sama seperti versi petugas |
| AS-23 | Logout | Sudah login | Sesi berakhir |

## C. Interaksi Admin dengan Petugas

Sebagian besar aktivitas yang sudah dijelaskan pada dua bagian di atas jarang berdiri sendiri, karena kebanyakan aksi dari satu peran menghasilkan efek yang dirasakan langsung oleh peran lainnya, entah lewat notifikasi in-app dan Telegram, atau lewat data yang tiba-tiba menjadi terlihat di sisi lain. Bagian ini merangkum secara eksplisit pasangan aksi-dan-dampak semacam itu, karena kombinasi inilah yang paling relevan untuk digambarkan sebagai activity diagram lintas-aktor, yaitu diagram yang menampilkan swimlane Admin dan Petugas dalam satu gambar yang sama.

| Kode | Pemicu (Admin atau Petugas) | Dampak kepada Pihak Lain |
|---|---|---|
| AU-01 | Admin membuat SPK baru (AS-03) | Seluruh petugas aktif menerima notifikasi "SPK Baru Tersedia" (disertai foto rambu pertama yang memiliki foto, apabila ada), muncul di Dashboard Petugas |
| AU-02 | Admin membatalkan SPK (AS-05) | Semua anggota tim yang sudah bergabung menerima notifikasi pembatalan |
| AU-03 | Admin membatalkan satu rambu (AS-06) | Tim menerima notifikasi; alasan pembatalan tampil di Detail SPK petugas, surat pengantar, dan Laporan Rambu |
| AU-04 | Admin menerima laporan saat validasi (AS-09) | Status rambu berubah menjadi Selesai, langsung terlihat oleh petugas di Detail SPK |
| AU-05 | Admin menolak laporan saat validasi (AS-09), mengisi catatan penolakan | Petugas menerima notifikasi; catatan tampil pada kartu rambu terkait dan pada formulir Kendala atau Laporan Pengerjaan, sehingga jelas apa yang perlu diperbaiki |
| AU-05b | Admin sekaligus memperpanjang tenggat waktu saat menolak (AS-09b) | Seluruh tim SPK menerima notifikasi terpisah bahwa tenggat waktu SPK berubah |
| AU-06 | Admin membuat SPK dari Temuan Kondisi (AS-11) | Status temuan berubah, dan apabila kebetulan admin memasukkan petugas yang sama, rambu itu jadi bisa dikerjakan lewat SPK baru |
| AU-07 | Admin menolak Temuan Kondisi (AS-12) | Petugas pelapor menerima notifikasi penolakan, mengarah ke halaman Detail Rambu terkait |
| AU-08 | Petugas mengajukan Kendala (US-07) | Tidak ada notifikasi kepada admin pada saat itu juga; alasannya langsung terlihat di Detail SPK, dan admin baru diberi tahu sekali lewat AU-10 begitu Laporan Akhir diajukan |
| AU-09 | Petugas mengirim Laporan Pengerjaan (US-09) | Sama seperti AU-08, tidak ada notifikasi per rambu, admin baru mengetahui lewat AU-10 |
| AU-10 | Petugas mengajukan Laporan Akhir (US-11) | SPK muncul di antrean Validasi Pengerjaan admin, admin pembuat SPK menerima notifikasi "Laporan Akhir Masuk", siap diproses |
| AU-11 | Petugas melaporkan Temuan Kondisi (US-12/US-17) | Semua admin menerima notifikasi, temuan masuk antrean Temuan Lapangan admin |
| AU-12 | Admin menambah akun petugas baru (AS-17) | Petugas yang bersangkutan bisa mulai login dan menemukan pekerjaan |
| AU-13 | Admin menonaktifkan akun petugas (AS-18) | Petugas itu tidak bisa login lagi (sesi yang sedang berjalan tidak otomatis terputus) |
| AU-14 | Setiap notifikasi lintas peran di atas (AU-01 sampai AU-11) | Apabila penerima sudah menghubungkan Telegram (US-21), pesan yang sama juga terkirim sebagai chat, di luar tampilan in-app |

## D. Interaksi Lainnya

Bagian terakhir ini mencakup proses yang berjalan secara otomatis di dalam sistem tanpa memerlukan aksi langsung dari admin atau petugas saat itu juga, interaksi dengan layanan eksternal, interaksi antar sesama petugas dalam satu tim, serta interaksi yang terjadi sebelum seorang pengguna berhasil login.

| Kode | Aktivitas | Pemicu | Hasil |
|---|---|---|---|
| L-01 | Perhitungan urgensi otomatis | Setiap SPK dibuat atau diedit (mengubah kolom yang tersimpan); setiap halaman yang menampilkan urgensi SPK Aktif dimuat (dihitung ulang secara live, tidak dari kolom yang tersimpan) | Urgensi (Tinggi/Sedang/Rendah) dihitung ulang dari sisa hari menuju tenggat waktu dan status prioritas |
| L-02 | Penyesuaian tenggat waktu otomatis untuk SPK lain | SPK baru yang ditandai Prioritas dibuat | Tenggat waktu SPK aktif non-prioritas lain ikut mundur (bersifat maksimal, tidak akumulatif), pemiliknya menerima notifikasi |
| L-03 | Perubahan status SPK menjadi Selesai secara otomatis | Semua rambu_pasang dalam SPK sudah Selesai atau Batal | Kolom `spk.status` berubah menjadi Selesai, `selesai_pada` dicatat sekali untuk keperluan analitik durasi |
| L-04 | Reset gerbang Laporan Akhir secara otomatis | Admin memproses validasi (AS-09), apa pun hasilnya | Kolom `laporan_akhir_diajukan_at` kembali kosong, SPK keluar dari antrean validasi sampai diajukan ulang |
| L-05 | Sinkronisasi warna pin peta | Setiap peta dimuat atau difilter | Warna dihitung di sisi klien dari status rambu_pasang atau SPK terkini, tanpa kolom tersimpan atau kueri tambahan |
| L-06 | Sistem mengirim notifikasi ke Telegram | Baris `Notifikasi` baru tercipta untuk pengguna yang sudah terhubung Telegram | Job antrean (`SendTelegramNotifikasi`) mengirim pesan atau foto lewat Bot API |
| L-07 | Sistem menerima konfirmasi link Telegram | Pengguna menekan Start pada bot (US-21) | Proses `telegram:poll` mencocokkan token sekali pakai, menyimpan `telegram_chat_id` pengguna terkait |
| L-08 | Pencatatan otomatis ke Audit Log | Setiap aksi bisnis penting (SPK dibuat/diedit/dibatalkan, laporan dikirim/divalidasi, dan sebagainya) | Baris log baru tersimpan, bisa dilihat lewat AS-19/US-23 |
| L-09 | Pencatatan otomatis ke System Error Log | Exception tak tertangani (level 500 ke atas) di dalam aplikasi | Baris error tersimpan, bisa dilihat lewat AS-20 |
| L-10 | Login, verifikasi 2FA, dan wizard reset kata sandi | Pengunjung belum login (tamu) | Akses terbatas hanya pada proses autentikasi, sebelum masuk ke aktivitas US- maupun AS- |
| L-11 | Perwakilan mendaftarkan rekan setim sekaligus | US-05/US-06 | Rekan setim tercatat sebagai anggota tim, bisa ikut bekerja secara fisik di lapangan namun tidak mengoperasikan formulir Kendala/Laporan/Ajukan Laporan Akhir, hak itu hanya milik perwakilan |
| L-12 | Perwakilan menghapus anggota tim (US-27) | Konfirmasi modal, anggota yang dihapus bukan perwakilan | Anggota terkait menerima notifikasi "Dikeluarkan dari Tim", tercatat di Audit Log |

---

## Catatan Penggunaan Dokumen Ini

Kode aktivitas (`US-xx`, `AS-xx`, `AU-xx`, `L-xx`) dipakai secara konsisten supaya setiap aktivitas bisa dirujuk dengan pasti, baik ketika nanti dipecah menjadi diagram aktivitas tersendiri per proses, maupun ketika dikelompokkan menjadi satu diagram besar per topik, misalnya "Siklus Pengerjaan SPK" yang bisa terdiri dari gabungan US-05 sampai US-11, ditambah AU-04/AU-05, ditambah AS-09.

Beberapa aktivitas muncul berulang di lebih dari satu kategori, misalnya Hubungkan Telegram muncul sebagai US-21/AS-22 sekaligus muncul dampaknya di AU-14. Ini sengaja dilakukan karena aktivitas tersebut memang perlu dilihat dari dua sudut pandang yang berbeda: aksi yang dilakukan pengguna itu sendiri, dan efek yang ditimbulkannya terhadap interaksi lintas peran.

Urutan proses yang lebih terperinci, mencakup percabangan status dan syarat setiap langkah, tersedia pada [ALUR-BISNIS.md](ALUR-BISNIS.md). Dokumen yang sedang Anda baca ini sengaja berfokus pada pertanyaan *aktivitas apa saja yang ada dan siapa yang terlibat di dalamnya*, bukan mengulang kembali penjelasan alur yang sudah dibahas secara mendalam di sana.
