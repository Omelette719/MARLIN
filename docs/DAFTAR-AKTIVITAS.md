# Daftar Aktivitas / Interaksi Sistem MARLIN

Katalog seluruh aktivitas dan interaksi dalam sistem, dikelompokkan per pasangan aktor: **Petugas <-> Sistem**, **Admin <-> Sistem**, **Admin <-> Petugas** (dampak yang saling terkait antara kedua peran, walau secara teknis dimediasi sistem), dan **Lainnya** (proses otomatis, integrasi eksternal, serta interaksi sebelum login).

Dokumen ini disiapkan sebagai bahan mentah untuk menyusun activity diagram, jadi tiap baris ditulis sebagai satu aktivitas/proses bisnis yang bisa langsung diturunkan jadi diagram alur (mulai, keputusan, aksi, selesai). Untuk detail teknis di balik tiap aktivitas (aturan bisnis, urutan status, dan alasan desainnya), lihat [ALUR-BISNIS.md](ALUR-BISNIS.md), [FITUR.md](FITUR.md), dan [HALAMAN.md](HALAMAN.md). Diverifikasi langsung dari kode per 2026-08-06.

---

## Daftar Isi

- [A. Interaksi Petugas <-> Sistem](#a-interaksi-petugas---sistem)
- [B. Interaksi Admin <-> Sistem](#b-interaksi-admin---sistem)
- [C. Interaksi Admin <-> Petugas](#c-interaksi-admin---petugas)
- [D. Interaksi Lainnya](#d-interaksi-lainnya)

---

## A. Interaksi Petugas <-> Sistem

Aktivitas yang dilakukan petugas lapangan langsung terhadap sistem.

| Kode | Aktivitas | Prasyarat / Trigger | Hasil |
|---|---|---|---|
| US-01 | Login (NIP + password) | Punya akun aktif | Masuk ke Dashboard Petugas; kalau 2FA aktif, diminta kode verifikasi dulu |
| US-02 | Reset password lewat wizard di halaman login | Lupa password, tahu NIP + password lama | Password baru tersimpan; dibatasi 5 percobaan/menit per NIP+IP |
| US-03 | Lihat Dashboard (Daftar Surat Aktif) | Sudah login | Melihat semua SPK aktif (bukan cuma yang diikuti) untuk mencari pekerjaan baru |
| US-04 | Lihat Detail SPK | Klik salah satu SPK aktif | Melihat detail surat, tim, daftar rambu, sebelum memutuskan bergabung |
| US-05 | Daftarkan Tim (gabung SPK sebagai perwakilan) | Belum ada tim terdaftar di SPK tsb | Perwakilan + rekan setim tercatat sebagai tim SPK; ada modal konfirmasi karena tidak bisa dibatalkan lewat sistem |
| US-06 | Tambah anggota tim belakangan | Sudah jadi perwakilan tim SPK tsb | Anggota baru tercatat; ada modal konfirmasi |
| US-07 | Isi Form Kendala | Perwakilan tim, rambu berstatus Belum/Revisi | Status rambu jadi Tertunda; admin (pembuat SPK) dapat notifikasi |
| US-08 | Edit/tukar Kendala jadi Laporan Pengerjaan | Rambu berstatus Tertunda, SPK belum ajukan Laporan Akhir | Kendala terhapus, laporan pengerjaan baru tercatat, status jadi Menunggu Validasi |
| US-09 | Isi Form Laporan Pengerjaan | Perwakilan tim, rambu berstatus Belum/Revisi | Status rambu jadi Menunggu Validasi; admin dapat notifikasi |
| US-10 | Edit/tukar Laporan Pengerjaan jadi Kendala | Rambu berstatus Menunggu Validasi, SPK belum ajukan Laporan Akhir | Laporan lama terhapus, kendala baru tercatat, status jadi Tertunda |
| US-11 | Ajukan Laporan Akhir | Perwakilan tim, semua rambu SPK sudah Tertunda/Menunggu Validasi/Selesai (minimal satu yang baru) | SPK masuk antrean Validasi Pengerjaan admin |
| US-12 | Lapor Temuan Kondisi | Siapa saja (tidak harus perwakilan), lihat rambu rusak di lapangan | `kondisi_terkini` rambu jadi Rusak, pin peta berubah warna, semua admin dapat notifikasi |
| US-13 | Lihat Riwayat Pekerjaan Saya | Sudah login | Daftar SPK yang pernah dikerjakan tim, filter per bulan |
| US-14 | Lihat SPK Sedang Dikerjakan | Sudah login | Subset dashboard, hanya SPK yang timnya sudah gabung |
| US-15 | Unduh Surat Pengantar (PDF) | Anggota tim SPK tsb (atau admin) | File PDF surat pengantar diunduh |
| US-16 | Lihat & filter Peta Interaktif | Sudah login | Pin rambu tampil sesuai filter (jenis, tingkat, tanggal); klik pin buka kartu info |
| US-17 | Lapor Temuan langsung dari kartu pin peta | Klik tombol di kartu info pin | Sama seperti US-12, jalan pintas dari peta |
| US-18 | Lihat Detail Rambu | Klik rambu di peta/daftar | Riwayat foto, kondisi, dan pekerjaan rambu tsb |
| US-19 | Lihat Jenis Rambu & Daftar Rambu | Sudah login | Referensi master data, read-only untuk petugas |
| US-20 | Lihat & baca Notifikasi in-app | Klik ikon lonceng di header | Badge belum-dibaca berkurang; notifikasi dengan tujuan halaman langsung membuka halaman terkait |
| US-21 | Hubungkan akun Telegram | Buka Settings > Telegram, tekan Start di bot | `telegram_chat_id` tersimpan; notifikasi berikutnya juga terkirim ke Telegram |
| US-22 | Putuskan akun Telegram | Sudah terhubung | `telegram_chat_id` dikosongkan, notifikasi Telegram berhenti |
| US-23 | Lihat Riwayat Aktivitas (Audit Log) | Sudah login | Hanya melihat aksi yang dia lakukan sendiri |
| US-24 | Ubah Profil (nama, foto) | Sudah login | Data profil diperbarui |
| US-25 | Ganti Password / kelola 2FA | Konfirmasi ulang password | Password baru aktif, atau 2FA diaktifkan/dinonaktifkan |
| US-26 | Logout | Sudah login | Sesi berakhir, kembali ke halaman login |
| US-27 | Hapus anggota tim (bukan perwakilan) | Perwakilan tim SPK tsb, konfirmasi modal | Anggota terkait dilepas dari tim SPK ini, dapat notifikasi; baris perwakilan sendiri tidak bisa dihapus lewat aksi ini |

## B. Interaksi Admin <-> Sistem

Aktivitas yang dilakukan admin langsung terhadap sistem (mengelola master data, memproses laporan, menghasilkan laporan tercetak).

| Kode | Aktivitas | Prasyarat / Trigger | Hasil |
|---|---|---|---|
| AS-01 | Login (NIP + password) + 2FA | Punya akun admin aktif | Masuk ke Dashboard Admin |
| AS-02 | Lihat Dashboard Admin | Sudah login | Ringkasan SPK aktif, rambu rusak, laporan menunggu validasi, widget peta ringkas |
| AS-03 | Buat Surat (SPK) | Isi form: jenis, alamat, deadline, daftar rambu (bisa banyak baris sekaligus) | SPK baru (status Aktif), urgensi terhitung otomatis; semua petugas aktif dapat notifikasi |
| AS-04 | Edit Surat (SPK) | SPK masih berstatus Aktif | Data header dan/atau daftar rambu berubah, tercatat di Audit Log |
| AS-05 | Batalkan SPK (seluruh surat) | SPK masih Aktif, konfirmasi modal | Status jadi Dibatalkan, semua rambu belum-selesai ikut Batal, tim yang gabung dapat notifikasi |
| AS-06 | Batalkan satu rambu dalam SPK | Isi alasan wajib lewat modal | Rambu itu jadi Batal, rambu lain di SPK sama tidak terganggu |
| AS-07 | Hapus permanen satu baris rambu_pasang | Status Belum/Batal, belum ada kendala/laporan | Baris rambu_pasang terhapus; baris rambu (aset fisik) tetap ada |
| AS-08 | Lihat Daftar Surat & Riwayat SPK | Sudah login | Daftar SPK aktif, dan arsip yang Selesai/Dibatalkan (filter status/jenis/pencarian) |
| AS-09 | Proses Validasi Pengerjaan (terima/tolak per rambu) | SPK sudah mengajukan Laporan Akhir | Rambu diterima jadi Selesai (dan rambu.sudah_terpasang/kondisi_terkini ikut berubah), atau ditolak jadi Revisi dengan catatan wajib |
| AS-10 | Lihat antrean Temuan Lapangan | Sudah login | Daftar laporan kondisi rusak yang belum ditindaklanjuti |
| AS-11 | Tindaklanjuti Temuan: buat SPK perbaikan dari temuan | Pilih temuan, isi form SPK | SPK perbaikan baru tercipta, status temuan jadi "sudah dibuatkan SPK" |
| AS-12 | Tindaklanjuti Temuan: tolak temuan | Pilih temuan | Status temuan jadi Ditolak, pelapor dapat notifikasi |
| AS-13 | Generate & unduh Laporan Bulanan (PDF) | Atur filter rentang tanggal/jenis rambu/status | File PDF rekap terunduh |
| AS-14 | Generate & unduh Laporan Rambu (PDF) | Atur filter rentang tanggal/jenis rambu/status | File PDF riwayat pekerjaan rambu terunduh |
| AS-15 | Kelola Jenis Rambu (tambah/ubah/hapus) | Sudah login sebagai admin | Master data jenis rambu berubah |
| AS-16 | Lihat Kelola Rambu | Sudah login | Daftar semua rambu, filter kondisi/wilayah |
| AS-17 | Tambah akun petugas/admin | Isi form data diri + password awal | Akun baru aktif, bisa langsung login |
| AS-18 | Edit akun (data diri, aktifkan/nonaktifkan) | Pilih akun dari Kelola Pengguna | Data akun berubah; akun nonaktif tidak bisa login lagi |
| AS-19 | Lihat Audit Log (seluruh sistem) | Sudah login sebagai admin | Melihat semua aksi bisnis kunci lintas pengguna |
| AS-20 | Lihat System Error Log | Sudah login sebagai admin | Melihat exception tak terduga (level 500+) yang tertangkap otomatis |
| AS-21 | Lihat & filter Peta, unduh PDF sebaran rambu | Sudah login, atur filter di widget peta | Cuplikan gambar peta + tabel analitik terangkum jadi PDF |
| AS-22 | Kelola Profil / Password / 2FA / Telegram | Sama seperti petugas (US-21, US-22, US-24, US-25) | Sama seperti versi petugas |
| AS-23 | Logout | Sudah login | Sesi berakhir |

## C. Interaksi Admin <-> Petugas

Aktivitas di atas jarang berdiri sendiri, kebanyakan aksi satu peran punya efek yang dirasakan peran lainnya (lewat notifikasi in-app/Telegram, atau lewat data yang jadi terlihat di sisi lain). Baris di bawah ini merangkum pasangan aksi-dampak itu secara eksplisit, karena ini yang paling relevan untuk activity diagram lintas-aktor (swimlane Admin dan Petugas dalam satu diagram).

| Kode | Pemicu (Admin atau Petugas) | Dampak ke pihak lain |
|---|---|---|
| AU-01 | Admin membuat SPK baru (AS-03) | Seluruh petugas aktif dapat notifikasi "SPK Baru Tersedia", muncul di Dashboard Petugas |
| AU-02 | Admin membatalkan SPK (AS-05) | Semua anggota tim yang sudah gabung dapat notifikasi pembatalan |
| AU-03 | Admin membatalkan satu rambu (AS-06) | Tim dapat notifikasi; alasan pembatalan tampil di Detail SPK petugas, surat pengantar, dan Laporan Rambu |
| AU-04 | Admin menerima laporan saat validasi (AS-09) | Status rambu berubah jadi Selesai, langsung terlihat petugas di Detail SPK |
| AU-05 | Admin menolak laporan saat validasi (AS-09), isi catatan penolakan | Petugas dapat notifikasi; catatan tampil di kartu rambu terkait dan di form Kendala/Laporan Pengerjaan, jadi jelas apa yang perlu diperbaiki |
| AU-06 | Admin membuat SPK dari Temuan Kondisi (AS-11) | Status temuan berubah, dan (kalau kebetulan admin memasukkan petugas yang sama) rambu itu jadi bisa dikerjakan lewat SPK baru |
| AU-07 | Admin menolak Temuan Kondisi (AS-12) | Petugas pelapor dapat notifikasi penolakan |
| AU-08 | Petugas mengajukan Kendala (US-07) | Admin (pembuat SPK) dapat notifikasi "Kendala Dilaporkan" |
| AU-09 | Petugas mengirim Laporan Pengerjaan (US-09) | Admin dapat notifikasi "Laporan Pengerjaan Masuk" |
| AU-10 | Petugas mengajukan Laporan Akhir (US-11) | SPK muncul di antrean Validasi Pengerjaan admin, siap diproses |
| AU-11 | Petugas melaporkan Temuan Kondisi (US-12/US-17) | Semua admin dapat notifikasi, temuan masuk antrean Temuan Lapangan admin |
| AU-12 | Admin menambah akun petugas baru (AS-17) | Petugas yang bersangkutan bisa mulai login dan menemukan pekerjaan |
| AU-13 | Admin menonaktifkan akun petugas (AS-18) | Petugas itu tidak bisa login lagi (sesi yang sedang berjalan tidak otomatis terputus) |
| AU-14 | Setiap notifikasi lintas peran di atas (AU-01 s/d AU-11) | Kalau penerima sudah menghubungkan Telegram (US-21), pesan yang sama juga terkirim sebagai chat, di luar tampilan in-app |

## D. Interaksi Lainnya

Proses yang berjalan otomatis di sistem (tanpa aksi langsung dari admin/petugas saat itu juga), interaksi dengan layanan eksternal, interaksi sesama petugas dalam satu tim, dan interaksi sebelum pengguna login.

| Kode | Aktivitas | Pemicu | Hasil |
|---|---|---|---|
| L-01 | Perhitungan urgensi otomatis | Setiap SPK dibuat/diedit | Urgensi (Tinggi/Sedang/Rendah) dihitung ulang dari sisa hari ke deadline dan status prioritas |
| L-02 | Penyesuaian deadline otomatis SPK lain | SPK baru ditandai Prioritas dibuat | Deadline SPK aktif non-prioritas lain ikut mundur (maksimal, tidak akumulatif), pemiliknya dapat notifikasi |
| L-03 | Perubahan status SPK jadi Selesai otomatis | Semua rambu_pasang dalam SPK sudah Selesai/Batal | `spk.status` jadi Selesai, `selesai_pada` dicatat sekali untuk analitik durasi |
| L-04 | Reset gate Laporan Akhir otomatis | Admin memproses validasi (AS-09), apapun hasilnya | `laporan_akhir_diajukan_at` kembali `null`, SPK keluar dari antrean validasi sampai diajukan ulang |
| L-05 | Sinkronisasi warna pin peta | Setiap peta dimuat/difilter | Warna dihitung di sisi client dari status rambu_pasang/SPK terkini, tanpa kolom tersimpan atau query tambahan |
| L-06 | Sistem mengirim notifikasi ke Telegram | `Notifikasi` baru tercipta untuk pengguna yang sudah terhubung Telegram | Job antrean (`SendTelegramNotifikasi`) mengirim pesan/foto lewat Bot API |
| L-07 | Sistem menerima konfirmasi link Telegram | Pengguna tekan Start di bot (US-21) | Proses `telegram:poll` mencocokkan token sekali-pakai, menyimpan `telegram_chat_id` pengguna terkait |
| L-08 | Pencatatan otomatis ke Audit Log | Setiap aksi bisnis kunci (SPK dibuat/diedit/dibatalkan, laporan dikirim/divalidasi, dst) | Baris log baru tersimpan, bisa dilihat lewat AS-19/US-23 |
| L-09 | Pencatatan otomatis ke System Error Log | Exception tak tertangani (level 500+) di aplikasi | Baris error tersimpan, bisa dilihat lewat AS-20 |
| L-10 | Login, verifikasi 2FA, dan wizard reset password | Pengunjung belum login (guest) | Akses terbatas ke proses autentikasi saja, sebelum masuk ke US-* atau AS-* |
| L-11 | Perwakilan mendaftarkan rekan setim sekaligus | US-05/US-06 | Rekan setim tercatat sebagai anggota tim, bisa ikut kerja fisik di lapangan tapi tidak mengoperasikan form Kendala/Laporan/Ajukan Laporan Akhir (hak itu cuma milik perwakilan) |
| L-12 | Perwakilan menghapus anggota tim (US-27) | Konfirmasi modal, anggota yang dihapus bukan perwakilan | Anggota terkait dapat notifikasi "Dikeluarkan dari Tim", tercatat di Audit Log |

---

## Catatan Penggunaan

- Kode aktivitas (US-xx, AS-xx, AU-xx, L-xx) dipakai supaya tiap aktivitas bisa dirujuk konsisten kalau nanti dipecah jadi activity diagram terpisah per proses, atau dikelompokkan jadi satu diagram besar per topik (mis. "Siklus Pengerjaan SPK" = US-05 s/d US-11 + AU-04/AU-05 + AS-09).
- Beberapa aktivitas berulang di lebih dari satu kategori (mis. Hubungkan Telegram muncul sebagai US-21/AS-22 dan juga dampaknya di AU-14) karena memang sengaja dilihat dari dua sudut pandang: aksi si pengguna sendiri, dan efeknya ke interaksi lintas peran.
- Urutan proses yang lebih detail (percabangan status, syarat tiap langkah) ada di [ALUR-BISNIS.md](ALUR-BISNIS.md), dokumen ini fokus ke *aktivitas apa saja yang ada dan siapa yang terlibat*, bukan mengulang alur detailnya.
