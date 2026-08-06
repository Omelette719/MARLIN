# Fitur Sistem MARLIN

Dokumen ini merinci setiap fitur per peran. Untuk gambaran umum sistem, lihat [README.md](../README.md). Untuk penjelasan tiap halaman/route, lihat [HALAMAN.md](HALAMAN.md). Untuk alur bisnis lengkap, lihat [ALUR-BISNIS.md](ALUR-BISNIS.md).

---

## Daftar Isi

- [Fitur Admin](#fitur-admin)
- [Fitur Petugas Lapangan](#fitur-petugas-lapangan)
- [Fitur Bersama (Admin & Petugas)](#fitur-bersama-admin--petugas)

---

## Fitur Admin

### Dashboard Admin
Ringkasan jumlah SPK aktif, rambu rusak, dan laporan yang menunggu validasi. Titik masuk utama admin ke sistem.

Ada juga widget peta ringkas dengan filter sendiri (jenis rambu, tingkat, rentang tanggal) dan tombol **Unduh PDF** yang menghasilkan laporan sebaran rambu lengkap dengan cuplikan gambar peta (tile + pin) sesuai filter yang lagi aktif, plus tabel analitik dan daftar rambu.

### Buat Surat (SPK)
- Dua jenis: **Pemasangan Baru** dan **Perbaikan**.
- Alamat dipecah jadi field terstruktur: **Jalan**, **RT**, **Kelurahan**. Kolom `wilayah` (teks gabungan seperti "Jl. X RT. Y Kel. Z") disusun otomatis dari ketiganya, dipakai di surat pengantar & tampilan lain tanpa perlu ubah kode di tempat lain.
- Banyak baris rambu dalam satu surat sekaligus (tombol **Tambah Rambu** di bagian bawah daftar).
- Untuk **Perbaikan**: bisa pilih rambu yang sudah terdaftar di sistem (searchable-select) atau catat rambu yang secara fisik sudah ada tapi belum pernah tercatat.
- **Perihal Permohonan** opsional, kalau kosong dibuat otomatis dari jenis pekerjaan & jenis rambu (mis. "pemasangan cermin tikungan").
- **Tanggal Survei** opsional, kalau diisi muncul di surat pengantar sebagai "(DISURVEI TGL ...)".
- **Petugas Survei** opsional, tapi wajib diisi kalau Tanggal Survei diisi. Nama-nama petugas yang melakukan survei, dicatat manual (bukan pilih dari akun terdaftar) karena tidak selalu petugas sistem yang turun survei. Tidak ditampilkan di surat pengantar, hanya di halaman Detail Surat.
- Data **Contact Person** (nama & telepon): kontak lokal yang bisa dihubungi di lapangan, bukan identitas RT/Perwakilan yang menandatangani surat. Nama RT/Perwakilan yang sebenarnya ditulis tangan langsung saat kunjungan lapangan, tidak diketik di sistem.
- **Urgensi** dihitung otomatis: ≤2 hari ke deadline atau ditandai **Prioritas** → Tinggi; ≤7 hari → Sedang; selebihnya → Rendah.
- Upload **File Referensi** (scan surat permohonan asli), maks. 5 MB.

### Edit Surat (SPK)
- Hanya tersedia selama status SPK masih **Aktif** (tidak bisa edit SPK yang sudah Selesai/Dibatalkan).
- Bisa ubah data header: Jalan/RT/Kelurahan, Perihal, Deadline, Prioritas, Asal Permintaan, Keterangan Asal, Tanggal Survei, Petugas Survei, File Referensi, Catatan, dan data Contact Person.
- Bisa juga ubah daftar rambunya: edit field rambu yang sudah ada, ganti ke rambu terdaftar lain (khusus Perbaikan), tambah rambu baru ke SPK yang sedang berjalan, **batalkan** satu rambu saja (wajib isi alasan, rambu lain di SPK yang sama tidak terganggu), atau **hapus permanen** satu baris rambu kalau memang murni salah input (status masih Belum/Batal dan belum ada kendala/laporan sama sekali).
- **Tidak bisa** diubah lewat Edit: jenis SPK (pasang baru vs perbaikan) untuk SPK itu sendiri, ini ditentukan sekali saat SPK dibuat.
- Setiap perubahan tercatat di Audit Log (`spk_diedit`, `rambu_pasang_dibatalkan`, `rambu_pasang_dihapus`).

### Batalkan SPK
- Tombol di halaman Detail Surat, hanya muncul selama status **Aktif**, dengan modal konfirmasi sebelum eksekusi.
- SPK **tidak pernah dihapus permanen**, statusnya diubah jadi `dibatalkan`.
- Semua `rambu_pasang` yang belum selesai otomatis ditandai `batal`.
- Rambu pasang-baru yang belum pernah benar-benar terpasang otomatis hilang dari peta (karena tidak pernah jadi objek nyata); rambu perbaikan tetap tampil di peta (kondisinya tetap tercatat rusak, menunggu SPK perbaikan lain).
- Tercatat di Audit Log (`spk_dibatalkan`).

### Daftar Surat
- Pencarian (nomor surat/wilayah), filter jenis pekerjaan.
- **Hanya menampilkan SPK berstatus Aktif**. Yang Selesai/Dibatalkan pindah ke halaman Riwayat SPK (lihat di bawah), supaya daftar ini tetap fokus ke pekerjaan yang masih berjalan.
- Kartu SPK menampilkan foto (dari foto survei rambu pertama yang punya foto) atau ikon placeholder.

### Riwayat SPK
- Arsip SPK yang sudah **Selesai** atau **Dibatalkan**, dipisah dari Daftar Surat supaya tidak bercampur dengan pekerjaan aktif.
- Pencarian (nomor surat/wilayah), filter status (Selesai/Dibatalkan), filter jenis pekerjaan.
- Klik "Lihat Detail" tetap membuka Detail Surat yang sama seperti SPK aktif (cuma tombol Edit/Batalkan tidak muncul lagi karena SPK-nya sudah final).
- Untuk SPK berstatus **Selesai**, Detail Surat menampilkan **Durasi Pengerjaan** (jumlah hari dari SPK dibuat sampai selesai) dan **Selisih dari Deadline** (badge hijau kalau selesai lebih cepat, merah kalau terlambat). Data ini juga tersedia lewat method di model `Spk` untuk dipakai di analitik dashboard/laporan berikutnya.
- Rambu yang dibatalkan satu per satu (lewat Edit Surat) menampilkan alasan pembatalannya di kartu rambu, juga muncul di surat pengantar dan Laporan Rambu.

### Validasi Pengerjaan
- Daftar SPK yang sudah mengajukan **Laporan Akhir** (lihat [ALUR-BISNIS.md](ALUR-BISNIS.md) untuk detail gate ini).
- Halaman detail menampilkan **semua** rambu dalam SPK itu, termasuk yang sudah divalidasi di putaran sebelumnya, supaya admin lihat konteks penuh (bukan cuma rambu yang baru masuk lagi setelah revisi).
- Per rambu, admin centang/tidak-centang untuk terima/tolak.
- Rambu yang tidak dicentang wajib diisi **catatan penolakan**, statusnya kembali ke `revisi`, petugas mengerjakan ulang khusus rambu itu (rambu lain di SPK yang sama tidak terganggu). Alasan ini juga ditampilkan kembali ke petugas di halaman Detail SPK dan form Kendala/Laporan-nya, supaya jelas apa yang perlu diperbaiki.
- Rambu yang diterima: `pasang_baru` → `rambu.sudah_terpasang = true`; `perbaikan` → `rambu.kondisi_terkini = baik`.
- Kalau semua rambu dalam SPK sudah `selesai`/`batal`, status SPK otomatis berubah jadi `selesai`, dan sistem mencatat `selesai_pada` untuk dipakai sebagai analitik durasi pengerjaan (lihat bagian Detail SPK di bawah).

### Laporan Bulanan
- Rekap rambu terpasang/belum terpasang dan SPK selesai/belum, filter fleksibel: rentang tanggal bebas, jenis rambu, dan status rambu (StatusRambuPasang) untuk tabel Detail Rambu.
- Bisa diexport ke PDF untuk dilaporkan ke atasan.

### Laporan Rambu
- Daftar riwayat pekerjaan rambu (per `rambu_pasang`), filter fleksibel: rentang tanggal bebas, jenis rambu, dan status.
- Sama seperti Laporan Bulanan, bisa diexport ke PDF; keduanya berbagi query filter yang sama (`App\Support\LaporanRambu`) supaya angkanya selalu konsisten.

### Kelola Jenis Rambu
- Master data kategori rambu: nama, spesifikasi standar, gambar referensi, bentuk ikon di peta (bulat/kotak).

### Kelola Rambu
- Daftar semua rambu terpasang di sistem, filter berdasar kondisi/wilayah.
- Rambu yang belum terpasang (`sudah_terpasang = false`) menampilkan kondisi sebagai **N/A**, bukan "Baik", karena rambu yang belum benar-benar ada secara fisik tidak punya kondisi nyata untuk dilaporkan.

### Kelola Pengguna
- Tambah/edit akun petugas & admin.
- **Aktifkan/nonaktifkan** akun (bukan hapus). Akun nonaktif tidak bisa login lagi (lihat [KEAMANAN.md](KEAMANAN.md)), tapi sesi yang sudah berjalan tidak otomatis terputus.

### Temuan Kondisi Belum Ditindaklanjuti
- Antrean laporan kerusakan dari petugas (`laporan_kondisi`) yang belum dibuatkan SPK perbaikan.

### Audit Log (Admin)
- Melihat **seluruh** jejak aksi bisnis kunci di sistem (SPK dibuat, laporan dikirim, validasi diterima/ditolak, SPK dibatalkan, dll), lintas semua pengguna.

### System Error Log
- Exception tak terduga (level 500 ke atas) di aplikasi otomatis tercatat di sini, memudahkan debugging tanpa perlu akses server/log file langsung.

---

## Fitur Petugas Lapangan

### Dashboard: Daftar Surat Aktif
- Menampilkan **semua** SPK berstatus aktif (bukan cuma yang sudah diikuti). Ini tempat petugas menemukan pekerjaan baru untuk diambil.
- Ringkasan: jumlah tugas aktif, tugas dalam progres, tugas mendekati deadline, tugas selesai bulan ini (khusus milik tim sendiri).

### Detail SPK & Gabung Tim
- Perwakilan tim mendaftarkan diri + rekan setim sekaligus (`daftarkanTim`), bukan tiap orang gabung sendiri-sendiri. Ada modal konfirmasi sebelum mendaftar karena belum ada cara keluar dari peran perwakilan lewat sistem.
- Bisa menambah anggota belakangan (`tambahAnggota`), khusus oleh perwakilan, dengan konfirmasi.
- Perwakilan juga bisa menghapus anggota non-perwakilan dari tim (`hapusAnggota`), misalnya kalau salah input orang. Baris perwakilan sendiri tidak bisa dihapus lewat sini. Anggota yang dihapus dapat notifikasi, dan aksinya tercatat di Audit Log.
- Unduh Surat Pengantar (PDF, dibuat on-the-fly).
- Untuk SPK berstatus **Selesai**, halaman ini juga menampilkan Durasi Pengerjaan dan Selisih dari Deadline, sama seperti versi admin.
- Kalau ada rambu yang ditolak admin saat validasi, alasan penolakannya ditampilkan di kartu rambu itu.

### Form Laporan Pengerjaan
- **Hanya bisa diisi oleh perwakilan tim** (`is_perwakilan = true`). Anggota lain boleh ikut kerja fisik di lapangan, tapi yang mengirim laporan cuma satu orang per SPK.
- Foto sesudah (wajib), koordinat GPS, catatan lapangan, daftar barang/bahan yang dipakai.
- Mengubah status rambu jadi `menunggu_validasi`.
- Bisa diedit ulang (foto baru opsional, field lain menimpa yang lama) selama SPK belum mengajukan Laporan Akhir. Kalau dibuka pada rambu yang sedang berstatus Tertunda (ada kendala tercatat), mengisi form ini otomatis mengganti kendala itu jadi laporan selesai.

### Form Kendala
- **Hanya bisa diisi oleh perwakilan tim**, sama seperti Laporan Pengerjaan.
- Kalau pemasangan/perbaikan tidak bisa dilanjutkan di lapangan, foto wajib, alasan wajib.
- Mengubah status rambu jadi `tertunda`.
- Sama seperti Laporan Pengerjaan: bisa diedit ulang, dan bisa dipakai untuk membatalkan laporan selesai yang sudah terlanjur dikirim (menggantinya jadi kendala), selama SPK belum mengajukan Laporan Akhir.

### Ajukan Laporan Akhir
- Hanya bisa diajukan oleh perwakilan tim, dan hanya kalau **semua** rambu dalam SPK sudah berstatus `tertunda`/`menunggu_validasi` (tidak ada yang masih `belum`/`revisi`).
- Ini yang memasukkan SPK ke antrean Validasi Pengerjaan admin.

### Lapor Temuan Kondisi
- Laporkan rambu yang ditemukan rusak di lapangan, **independen dari SPK aktif** manapun.
- Foto wajib. Langsung mengubah `rambu.kondisi_terkini = rusak` supaya pin di peta segera berubah warna.

### Riwayat Pekerjaan Saya
- Semua SPK yang pernah dikerjakan tim, bisa difilter per bulan, sebagai bukti kerja.

### SPK Sedang Dikerjakan
- Daftar SPK yang timnya sudah terdaftar untuk mengerjakan (subset dari Dashboard, hanya yang sudah diikuti).

---

## Fitur Bersama (Admin & Petugas)

### Peta Interaktif
- Semua rambu tampil sebagai pin di peta (Leaflet + OpenStreetMap), warna sesuai status:

  | Warna | Arti |
  |---|---|
  | Cyan | Menunggu Validasi (diprioritaskan di atas merah, laporan sudah dikirim, giliran admin) |
  | Merah | Urgent / Prioritas / Urgensi Tinggi |
  | Kuning | Kondisi rusak, atau sedang dalam proses perbaikan |
  | Biru tua | Selesai & kondisi baik |
  | Abu-abu | Belum dikerjakan |

- Klik pin untuk membuka kartu info (foto, jenis rambu, lokasi, koordinat, status), klik lagi atau tombol **X** di kartu untuk menutup.
- Kartu otomatis membuka di sisi kiri/kanan pin tergantung ruang kosong di layar.
- Tombol **Detail Rambu** dan **Google Maps** di dalam kartu.
- Petugas dapat tombol tambahan **Lapor Temuan Kondisi** langsung dari kartu pin.

### Detail Rambu
- Riwayat foto & kondisi, riwayat pekerjaan (tiap entri link ke Detail SPK terkait).
- Kalau rambu sedang menunggu validasi/tertunda, admin dapat tombol **Ke Halaman Validasi** langsung ke SPK terkait.

### Notifikasi
- Bell icon di header dengan badge bulat merah menampilkan jumlah belum dibaca (maks. tampil "9+").
- Notifikasi meliputi: SPK baru tersedia, laporan diterima/ditolak, kendala diajukan, temuan kondisi, dll, tergantung peran.
- Notifikasi yang berkaitan dengan sesuatu yang bisa dilihat langsung (SPK, validasi, temuan) punya tombol **Lihat** yang menandai notifikasi terbaca sekaligus membuka halaman terkait.
- Bisa dihubungkan ke **Telegram** (lewat Settings) supaya notifikasi yang sama juga masuk sebagai pesan chat, tidak cuma tampil di halaman ini.

### Riwayat Aktivitas (Audit Log)
- Admin melihat seluruh aktivitas sistem.
- Petugas hanya melihat aktivitas yang **dia lakukan sendiri** (bukan aktivitas admin/rekan setim di SPK yang sama).

### Jenis Rambu & Daftar Rambu
- Kedua halaman ini bisa diakses admin maupun petugas (read-only untuk petugas), referensi bersama.

### Settings
- Profil (nama, foto, data diri) dan Keamanan (ganti password, aktifkan/kelola 2FA). Halaman Keamanan memerlukan konfirmasi ulang password sebelum diakses (lihat [KEAMANAN.md](KEAMANAN.md)).
- Halaman **Telegram**: hubungkan akun Telegram lewat link sekali pakai (buka bot, tekan Start), lalu semua notifikasi yang sudah dibuat di sistem juga otomatis terkirim ke chat itu. Bisa diputuskan kapan saja.
