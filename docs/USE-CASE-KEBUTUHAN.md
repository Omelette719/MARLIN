# Use Case, Kebutuhan Fungsional, dan Kebutuhan Non-Fungsional — Sistem MARLIN

> Dokumen ini disusun dengan format mengikuti draft PRD awal proyek (`PRD PKL (1).docx`), tetapi isinya diambil dari **sistem yang sudah benar-benar dibangun dan berjalan**, bukan dari draft rencana awal. Sumber kebenaran: [FITUR.md](FITUR.md), [ALUR-BISNIS.md](ALUR-BISNIS.md), [DAFTAR-AKTIVITAS.md](DAFTAR-AKTIVITAS.md), [KEAMANAN.md](KEAMANAN.md), dan [DATABASE.md](DATABASE.md). Kode referensi pada kolom **Catatan** (`US-xx`/`AS-xx`/`AU-xx`) merujuk ke [DAFTAR-AKTIVITAS.md](DAFTAR-AKTIVITAS.md) untuk detail lebih lanjut.

---

## Daftar Isi

- [User Stories / Use Case](#user-stories--use-case)
- [Kebutuhan Fungsional](#kebutuhan-fungsional)
- [Kebutuhan Non-Fungsional](#kebutuhan-non-fungsional)

---

## User Stories / Use Case

| Modul | Use Case | User Story | Prioritas | Aktor | Catatan |
|---|---|---|---|---|---|
| Otentikasi | Login Sistem | Sebagai pengguna terdaftar, saya ingin login memakai NIP dan password (serta kode 2FA jika aktif), sehingga saya bisa mengakses sistem sesuai peran saya. | Tinggi | Admin & Petugas | Ref: US-01/AS-01 |
| Otentikasi | Reset Password Mandiri | Sebagai pengguna yang lupa password, saya ingin menggantinya sendiri lewat wizard di halaman login (NIP + password lama), sehingga saya tidak perlu minta bantuan admin. | Sedang | Admin & Petugas | Dibatasi 5 percobaan/menit. Ref: US-02 |
| Otentikasi | Mengelola Profil | Sebagai pengguna, saya ingin mengubah nama, nama panggilan, dan foto profil saya, sehingga data diri saya tetap akurat. | Rendah | Admin & Petugas | Ref: US-24 |
| Otentikasi | Mengelola Keamanan Akun | Sebagai pengguna, saya ingin mengganti password dan mengaktifkan/menonaktifkan 2FA saya sendiri, sehingga akun saya tetap aman. | Sedang | Admin & Petugas | Wajib konfirmasi ulang password. Ref: US-25 |
| Otentikasi | Mengelola Akun Pengguna | Sebagai admin, saya ingin menambah, mengubah, serta mengaktifkan/menonaktifkan akun petugas dan admin lain, sehingga akses ke sistem selalu terkendali dan terpusat. | Tinggi | Admin | Tidak ada registrasi publik. Ref: AS-17/AS-18 |
| Otentikasi | Logout | Sebagai pengguna, saya ingin keluar dari sistem, sehingga sesi saya berakhir dengan aman di perangkat bersama. | Rendah | Admin & Petugas | Ref: US-26/AS-23 |
| Dashboard | Melihat Dashboard Admin | Sebagai admin, saya ingin melihat ringkasan SPK aktif, rambu rusak, dan laporan menunggu validasi begitu login, sehingga saya tahu prioritas kerja hari itu. | Tinggi | Admin | Termasuk widget peta ringkas. Ref: AS-02 |
| Dashboard | Melihat Dashboard Petugas | Sebagai petugas, saya ingin melihat semua SPK aktif (bukan cuma yang saya ikuti) begitu login, sehingga saya bisa menemukan pekerjaan baru untuk diambil. | Tinggi | Petugas | Ref: US-03 |
| Master Data | Mengelola Jenis Rambu | Sebagai admin, saya ingin menambah, mengubah, dan menghapus jenis rambu beserta spesifikasinya, sehingga master data rambu selalu mutakhir. | Sedang | Admin | Ref: AS-15 |
| Master Data | Melihat Daftar Rambu | Sebagai pengguna, saya ingin melihat daftar seluruh rambu terdaftar beserta kondisinya, sehingga saya punya referensi aset yang ada. | Sedang | Admin & Petugas | Read-only untuk petugas. Ref: AS-16/US-19 |
| Peta Digital | Melihat Peta Rambu | Sebagai pengguna, saya ingin melihat seluruh titik rambu di peta dengan warna sesuai status terkini, sehingga saya cepat menilai kondisi lapangan tanpa membuka tiap rambu satu-satu. | Tinggi | Admin & Petugas | Ref: US-16 |
| Peta Digital | Melihat Kartu Info Pin | Sebagai pengguna, saya ingin mengklik pin di peta untuk melihat detail singkat rambu tersebut, sehingga saya tidak perlu berpindah halaman untuk informasi dasar. | Sedang | Admin & Petugas | — |
| Peta Digital | Mengunduh PDF Sebaran Rambu | Sebagai admin, saya ingin mengunduh cuplikan peta beserta tabel analitiknya sebagai PDF, sehingga saya bisa melaporkannya ke atasan. | Rendah | Admin | Ref: AS-21 |
| Peta Digital | Melapor Temuan dari Peta | Sebagai petugas, saya ingin melapor rambu rusak langsung dari kartu info pin di peta, sehingga saya tidak perlu berpindah ke halaman lain saat menemukannya di lapangan. | Sedang | Petugas | Jalan pintas dari US-12. Ref: US-17 |
| Penjadwalan (SPK) | Membuat Surat (SPK) | Sebagai admin, saya ingin membuat Surat Perintah Kerja untuk pemasangan baru atau perbaikan dengan banyak baris rambu sekaligus, sehingga penugasan lapangan tercatat resmi dan terstruktur. | Tinggi | Admin | Urgensi terhitung otomatis. Ref: AS-03 |
| Penjadwalan (SPK) | Mengedit Surat (SPK) | Sebagai admin, saya ingin mengubah data SPK yang masih aktif (header maupun daftar rambunya), sehingga saya bisa mengoreksi kesalahan atau menyesuaikan kondisi terbaru tanpa membuat surat baru. | Sedang | Admin | Ref: AS-04 |
| Penjadwalan (SPK) | Membatalkan SPK | Sebagai admin, saya ingin membatalkan seluruh SPK yang tidak jadi dikerjakan, sehingga statusnya jelas tanpa menghapus riwayatnya. | Sedang | Admin | Tidak pernah hard-delete. Ref: AS-05 |
| Penjadwalan (SPK) | Membatalkan/Menghapus Satu Rambu | Sebagai admin, saya ingin membatalkan atau menghapus satu baris rambu dalam SPK tanpa mengganggu rambu lain, sehingga koreksi kecil tidak perlu membatalkan seluruh surat. | Rendah | Admin | Ref: AS-06/AS-07 |
| Penjadwalan (SPK) | Melihat Daftar Surat & Riwayat SPK | Sebagai admin, saya ingin melihat daftar SPK aktif terpisah dari arsip SPK selesai/dibatalkan, sehingga saya fokus ke pekerjaan yang masih berjalan. | Tinggi | Admin | Ref: AS-08 |
| Penjadwalan (SPK) | Mendaftarkan Tim | Sebagai petugas, saya ingin mendaftarkan diri sebagai perwakilan beserta rekan setim ke sebuah SPK, sehingga tim saya resmi bertanggung jawab mengerjakannya. | Tinggi | Petugas | Tidak bisa dibatalkan lewat sistem. Ref: US-05 |
| Penjadwalan (SPK) | Menambah/Menghapus Anggota Tim | Sebagai perwakilan tim, saya ingin menambah atau menghapus anggota tim non-perwakilan di kemudian hari, sehingga susunan tim tetap akurat kalau ada perubahan. | Rendah | Petugas | Ref: US-06/US-27 |
| Penjadwalan (SPK) | Mengunduh Surat Pengantar | Sebagai admin atau anggota tim, saya ingin mengunduh Surat Pengantar dalam bentuk PDF, sehingga ada dokumen resmi yang bisa dibawa ke lapangan. | Sedang | Admin & Petugas | Ref: US-15 |
| Penjadwalan (SPK) | Melihat Jadwal Tugas | Sebagai petugas, saya ingin melihat SPK yang sedang saya kerjakan dan riwayat pekerjaan saya, sehingga saya bisa memantau progres kerja tim saya. | Sedang | Petugas | Ref: US-13/US-14 |
| Eksekusi Lapangan | Mengisi Laporan Pengerjaan | Sebagai perwakilan tim, saya ingin mengirim laporan pengerjaan (foto sesudah + GPS wajib) untuk sebuah rambu, sehingga admin punya bukti pekerjaan sudah selesai untuk divalidasi. | Tinggi | Petugas | Hanya perwakilan. Ref: US-09 |
| Eksekusi Lapangan | Mengisi Form Kendala | Sebagai perwakilan tim, saya ingin melaporkan kendala (foto + alasan wajib) kalau pekerjaan tidak bisa dilanjutkan, sehingga admin tahu status sebenarnya di lapangan. | Tinggi | Petugas | Ref: US-07 |
| Eksekusi Lapangan | Menukar Kendala/Laporan | Sebagai perwakilan tim, saya ingin mengedit atau menukar jenis laporan (kendala ↔ selesai) sebelum Laporan Akhir diajukan, sehingga saya bisa mengoreksi kalau situasi lapangan berubah. | Rendah | Petugas | Ref: US-08/US-10 |
| Eksekusi Lapangan | Mengajukan Laporan Akhir | Sebagai perwakilan tim, saya ingin mengajukan Laporan Akhir setelah seluruh rambu dalam SPK tertangani, sehingga SPK masuk antrean validasi admin dalam satu batch, bukan satu-satu. | Tinggi | Petugas | Ref: US-11 |
| Validasi & Persetujuan | Memvalidasi Laporan Pengerjaan | Sebagai admin, saya ingin meninjau dan menerima/menolak tiap rambu dalam SPK yang sudah mengajukan Laporan Akhir, sehingga hanya pekerjaan yang benar-benar sesuai standar yang ditandai selesai. | Tinggi | Admin | Kendala tidak bisa diterima. Ref: AS-09 |
| Validasi & Persetujuan | Memperpanjang Deadline Saat Menolak | Sebagai admin, saya ingin memberi kelonggaran deadline SPK langsung dari form penolakan, sehingga petugas punya waktu wajar untuk merevisi tanpa saya harus membuka halaman terpisah. | Rendah | Admin | Ref: AS-09b |
| Temuan Kondisi | Melapor Temuan Kondisi | Sebagai siapa saja yang login, saya ingin melaporkan rambu yang saya temukan rusak di lapangan (di luar SPK aktif manapun), sehingga kerusakan segera tercatat dan pin peta berubah warna. | Sedang | Admin & Petugas | Ref: US-12 |
| Temuan Kondisi | Menindaklanjuti Temuan | Sebagai admin, saya ingin membuat SPK perbaikan langsung dari sebuah temuan kondisi, atau menolaknya kalau tidak perlu ditindaklanjuti, sehingga antrean temuan tidak menumpuk tanpa kejelasan. | Sedang | Admin | Ref: AS-11/AS-12 |
| Riwayat & Pelaporan | Membuat Laporan Bulanan/Rambu | Sebagai admin, saya ingin mengekspor rekap pekerjaan rambu ke PDF dengan filter tanggal/jenis/status, sehingga saya punya bahan laporan ke atasan. | Sedang | Admin | Ref: AS-13/AS-14 |
| Riwayat & Pelaporan | Melihat Detail Rambu | Sebagai pengguna, saya ingin melihat riwayat foto, kondisi, dan pekerjaan satu rambu tertentu, sehingga saya paham histori lengkapnya sebelum mengambil keputusan. | Sedang | Admin & Petugas | Ref: US-18 |
| Notifikasi | Melihat Notifikasi In-App | Sebagai pengguna, saya ingin melihat dan membaca notifikasi lewat ikon lonceng, sehingga saya tidak ketinggalan peristiwa penting yang relevan dengan peran saya. | Tinggi | Admin & Petugas | Ref: US-20 |
| Notifikasi | Menghubungkan Akun Telegram | Sebagai pengguna, saya ingin menghubungkan akun saya ke bot Telegram, sehingga saya tetap dapat notifikasi walau tidak sedang membuka sistem. | Rendah | Admin & Petugas | Ref: US-21/US-22 |
| Audit | Melihat Riwayat Aktivitas (Seluruh Sistem) | Sebagai admin, saya ingin melihat seluruh jejak aksi bisnis kunci lintas pengguna, sehingga saya bisa menelusuri siapa melakukan apa kalau terjadi sengketa/kesalahan. | Sedang | Admin | Ref: AS-19 |
| Audit | Melihat Aktivitas Sendiri | Sebagai petugas, saya ingin melihat riwayat aktivitas yang saya lakukan sendiri, sehingga saya punya bukti kerja saya sendiri. | Rendah | Petugas | Ref: US-23 |

---

## Kebutuhan Fungsional

### 1. Modul Autentikasi & Manajemen Akun

- **FR-1.1** Sistem harus menyediakan login berbasis **NIP + password** (bukan email), dengan verifikasi kredensial kustom yang sekaligus memeriksa status akun aktif/nonaktif.
- **FR-1.2** Sistem harus mendukung **Two-Factor Authentication (2FA)** opsional per akun, yang wajib dikonfirmasi ulang dengan password sebelum bisa diaktifkan/diubah.
- **FR-1.3** Sistem harus membatasi percobaan login gagal maksimal **5 kali/menit** per kombinasi NIP+IP, dan verifikasi kode 2FA maksimal 5 kali/menit per sesi login.
- **FR-1.4** Sistem harus menyediakan wizard **reset password mandiri** di halaman login (NIP + password lama), dibatasi 5 percobaan/menit per NIP+IP.
- **FR-1.5** Sistem harus menerapkan **kontrol akses berbasis peran (RBAC)**: Admin dan Petugas Lapangan. Percobaan mengakses halaman peran lain harus dialihkan ke dashboard sesuai peran pengguna, bukan menampilkan halaman error.
- **FR-1.6** Sistem harus membatasi pembuatan akun **hanya oleh Admin**; tidak ada halaman registrasi mandiri/publik.
- **FR-1.7** Admin harus dapat menambah, mengubah data, serta **mengaktifkan/menonaktifkan** akun Petugas dan Admin lain. Akun yang dinonaktifkan tidak bisa login lagi.
- **FR-1.8** Setiap pengguna harus dapat mengelola profil sendiri (nama, nama panggilan, foto).
- **FR-1.9** Setiap pengguna harus dapat mengganti password dan mengelola 2FA miliknya sendiri, dengan konfirmasi ulang password sebelum halaman Keamanan bisa diakses.
- **FR-1.10** Sistem harus menyediakan logout yang mengakhiri sesi pengguna.

### 2. Modul Dashboard

- **FR-2.1** Dashboard Admin harus menampilkan ringkasan jumlah SPK aktif, rambu berkondisi rusak, dan laporan yang menunggu validasi.
- **FR-2.2** Dashboard Admin harus menampilkan widget peta ringkas dengan filter sendiri (jenis rambu, tingkat urgensi, rentang tanggal) dan tombol unduh laporan sebaran rambu dalam bentuk PDF.
- **FR-2.3** Dashboard Petugas ("Daftar Surat Aktif") harus menampilkan **seluruh** SPK berstatus aktif (bukan hanya yang sudah diikuti timnya), agar petugas bisa menemukan pekerjaan baru.
- **FR-2.4** Dashboard Petugas harus menampilkan ringkasan jumlah tugas aktif, tugas dalam progres, tugas mendekati deadline, dan tugas selesai bulan berjalan milik timnya sendiri.

### 3. Modul Manajemen Data Rambu (Master Data)

- **FR-3.1** Admin harus dapat mengelola (tambah/ubah/hapus) data **Jenis Rambu**: nama, spesifikasi standar, gambar referensi, dan bentuk ikon peta.
- **FR-3.2** Sistem harus menyediakan daftar seluruh **Rambu** terdaftar, dapat difilter berdasarkan kondisi dan wilayah; dapat diakses admin (kelola penuh) maupun petugas (lihat saja/read-only).
- **FR-3.3** Sistem harus menampilkan kondisi rambu yang belum terpasang sebagai **"N/A"**, bukan "Baik", karena belum menjadi objek fisik nyata.

### 4. Modul Peta Digital Interaktif

- **FR-4.1** Sistem harus menampilkan seluruh titik rambu sebagai pin pada peta interaktif (Leaflet + OpenStreetMap), dengan warna pin yang mencerminkan status pekerjaan/kondisi terkini secara real-time tanpa perlu reload halaman atau query tambahan.
- **FR-4.2** Titik rambu baru pada peta hanya dapat muncul melalui pembuatan SPK (bukan input manual langsung di peta).
- **FR-4.3** Klik pin harus membuka kartu info berisi foto, jenis rambu, lokasi, koordinat, dan status, dengan tombol navigasi ke Google Maps dan ke halaman Detail Rambu.
- **FR-4.4** Sistem harus menyediakan tombol pelaporan Temuan Kondisi langsung dari kartu info pin (khusus Petugas).
- **FR-4.5** Peta harus mendukung filter berdasarkan jenis rambu, tingkat urgensi, dan rentang tanggal.

### 5. Modul Penjadwalan & Penugasan (SPK)

- **FR-5.1** Admin harus dapat membuat SPK dengan dua jenis pekerjaan: **Pemasangan Baru** dan **Perbaikan**, dengan banyak baris rambu dalam satu surat.
- **FR-5.2** Sistem harus menghitung **urgensi SPK secara otomatis** berdasarkan sisa hari ke deadline: ditandai Prioritas atau sisa ≤2 hari → Tinggi; sisa ≤7 hari → Sedang; selebihnya → Rendah. Untuk SPK berstatus Aktif, urgensi dihitung ulang secara **live** setiap ditampilkan, bukan hanya dibaca dari kolom tersimpan.
- **FR-5.3** Sistem harus menolak Deadline SPK yang bukan tanggal **setelah** hari ini (tidak boleh hari ini atau tanggal yang sudah lewat).
- **FR-5.4** Admin harus dapat mengedit data SPK (header maupun daftar rambu) selama status SPK masih **Aktif**.
- **FR-5.5** Admin harus dapat membatalkan seluruh SPK (status menjadi Dibatalkan, **tidak pernah dihapus permanen**), dengan seluruh rambu yang belum selesai ikut ditandai Batal.
- **FR-5.6** Admin harus dapat membatalkan satu baris rambu dalam SPK secara terpisah (wajib isi alasan), tanpa memengaruhi rambu lain dalam SPK yang sama maupun status SPK-nya.
- **FR-5.7** Admin harus dapat menghapus permanen satu baris rambu_pasang, **hanya** jika statusnya masih Belum/Batal dan belum ada kendala/laporan sama sekali.
- **FR-5.8** Sistem harus menampilkan Daftar Surat (SPK aktif) terpisah dari Riwayat SPK (SPK Selesai/Dibatalkan), masing-masing dengan pencarian dan filter.
- **FR-5.9** Petugas (sebagai perwakilan) harus dapat mendaftarkan diri beserta rekan setim ke sebuah SPK dalam satu aksi, dan menambah/menghapus anggota tim non-perwakilan di kemudian hari.
- **FR-5.10** Sistem harus menyediakan unduh Surat Pengantar dalam bentuk PDF, dibatasi hanya untuk admin atau anggota tim SPK terkait.
- **FR-5.11** Saat SPK baru ditandai Prioritas dibuat, sistem harus otomatis memundurkan deadline SPK aktif non-prioritas lain yang relevan (maksimal, tidak akumulatif), disertai notifikasi ke pemiliknya.

### 6. Modul Eksekusi Lapangan (Antarmuka Petugas)

- **FR-6.1** Hanya **perwakilan tim** (`is_perwakilan = true`) yang boleh mengisi Form Laporan Pengerjaan, Form Kendala, dan mengajukan Laporan Akhir.
- **FR-6.2** Form Laporan Pengerjaan harus mewajibkan foto sesudah dan koordinat GPS; sistem menolak pengiriman tanpa keduanya beserta pesan error yang jelas.
- **FR-6.3** Form Kendala harus mewajibkan foto dan alasan kendala.
- **FR-6.4** Mengisi Form Laporan Pengerjaan mengubah status rambu menjadi **Menunggu Validasi**; mengisi Form Kendala mengubah status menjadi **Tertunda**.
- **FR-6.5** Petugas (perwakilan) harus dapat mengedit ulang atau menukar jenis laporan (Kendala ↔ Laporan Pengerjaan) untuk rambu yang sama, selama SPK belum mengajukan Laporan Akhir.
- **FR-6.6** Sistem harus mengizinkan **"Ajukan Laporan Akhir"** hanya jika seluruh rambu dalam SPK sudah berstatus Tertunda/Menunggu Validasi/Selesai (minimal satu yang baru), dan tidak ada satupun yang masih Belum/Revisi.
- **FR-6.7** Sistem harus menyediakan pelaporan **Temuan Kondisi** yang independen dari SPK aktif manapun, dapat dilakukan oleh siapa saja yang login (bukan hanya perwakilan), dengan foto wajib.
- **FR-6.8** Sistem harus menyediakan tombol navigasi eksternal (Google Maps) dari Detail SPK/kartu peta ke lokasi rambu.
- **FR-6.9** Sistem harus menampilkan **Riwayat Pekerjaan Saya** dan **SPK Sedang Dikerjakan** bagi petugas, dengan filter per bulan.

### 7. Modul Validasi & Persetujuan (Admin)

- **FR-7.1** Sistem harus menampilkan antrean **Validasi Pengerjaan** hanya untuk SPK yang sudah mengajukan Laporan Akhir (validasi dilakukan per-batch per-SPK, bukan per-rambu begitu dikirim).
- **FR-7.2** Admin harus dapat menandai tiap rambu sebagai diterima/ditolak. Rambu berstatus **Tertunda** (kendala) **tidak dapat** diterima/dicentang — dipaksa sistem di sisi server tetap masuk jalur revisi apapun input yang dikirim dari client.
- **FR-7.3** Menolak sebuah rambu wajib disertai **catatan penolakan**, mengubah statusnya menjadi **Revisi**; rambu lain dalam SPK yang sama tidak terpengaruh.
- **FR-7.4** Menerima sebuah rambu mengubah statusnya menjadi **Selesai**, sekaligus memperbarui `sudah_terpasang` (untuk Pemasangan Baru) atau `kondisi_terkini` (untuk Perbaikan) pada rambu terkait.
- **FR-7.5** Admin harus dapat memperpanjang deadline SPK langsung dari form penolakan validasi (opsional, checkbox "beri kelonggaran"), sebagai satu transaksi dengan penolakan rambunya, tercatat di Audit Log dan memicu notifikasi ke seluruh tim.
- **FR-7.6** Sistem harus mereset gate Laporan Akhir (`laporan_akhir_diajukan_at` menjadi kosong) setiap kali admin memproses validasi, apapun hasilnya, agar SPK yang masih ada rambu Revisi otomatis keluar dari antrean sampai diajukan ulang.
- **FR-7.7** Sistem harus otomatis mengubah status SPK menjadi **Selesai** ketika seluruh rambu di dalamnya sudah Selesai/Batal, dan mencatat waktu selesainya untuk analitik Durasi Pengerjaan & Selisih dari Deadline.

### 8. Modul Temuan Kondisi

- **FR-8.1** Sistem harus menyediakan antrean **Temuan Lapangan** bagi Admin, berisi laporan kondisi rusak yang belum ditindaklanjuti.
- **FR-8.2** Admin harus dapat menindaklanjuti temuan dengan membuat SPK perbaikan baru (foto temuan otomatis dibawa sebagai foto survei rambu di SPK baru) **atau** menolak temuan tersebut, dengan notifikasi ke pelapor pada kedua kasus.

### 9. Modul Riwayat & Pelaporan

- **FR-9.1** Sistem harus menyediakan **Laporan Bulanan** dan **Laporan Rambu** dengan filter rentang tanggal, jenis rambu, dan status, keduanya dapat diekspor ke PDF dan berbagi sumber query yang sama agar angkanya konsisten.
- **FR-9.2** Sistem harus menyediakan **Riwayat SPK** (arsip SPK Selesai/Dibatalkan), termasuk Durasi Pengerjaan dan Selisih dari Deadline untuk SPK berstatus Selesai.
- **FR-9.3** Sistem harus menampilkan riwayat foto & kondisi per rambu pada halaman **Detail Rambu**, dengan foto utama yang otomatis mengikuti kondisi terkini (foto sesudah terbaru menang atas foto survei).

### 10. Modul Notifikasi

- **FR-10.1** Sistem harus mengirimkan notifikasi in-app untuk peristiwa kunci (SPK baru tersedia, Laporan Akhir masuk, laporan diterima/ditolak, temuan kondisi, dll.) sesuai peran penerima.
- **FR-10.2** Sistem harus menampilkan badge jumlah notifikasi belum dibaca (maksimal tampil "9+") pada ikon lonceng di header.
- **FR-10.3** Sistem harus mendukung penghubungan akun pengguna ke **Telegram** lewat token sekali pakai, sehingga notifikasi yang sama juga terkirim sebagai pesan chat (termasuk foto jika notifikasinya punya lampiran).
- **FR-10.4** Notifikasi Telegram harus tetap terkirim meski URL tujuan pada notifikasi tidak dapat diakses publik (mis. domain pengembangan lokal), dengan menghilangkan tombol tautan alih-alih membatalkan seluruh pesan.
- **FR-10.5** Pengguna harus dapat memutuskan hubungan akun Telegram-nya kapan saja.

### 11. Modul Audit Trail & Log Sistem

- **FR-11.1** Sistem harus mencatat setiap aksi bisnis kunci (SPK dibuat/diedit/dibatalkan, laporan dikirim/diterima/ditolak, kendala diajukan, deadline diperpanjang, dll.) ke **Audit Log** secara append-only.
- **FR-11.2** Admin harus dapat melihat seluruh Audit Log lintas pengguna; Petugas hanya dapat melihat aktivitas yang dilakukannya **sendiri**.
- **FR-11.3** Sistem harus mencatat exception tak tertangani (level 500 ke atas) secara otomatis ke **System Error Log**, dapat dilihat Admin tanpa perlu akses server/log file langsung.

---

## Kebutuhan Non-Fungsional

### 1. Security

- **NFR-1.1** Sistem harus menerapkan RBAC (Admin/Petugas) di seluruh grup route, dengan redirect ke dashboard sesuai peran saat percobaan akses lintas peran.
- **NFR-1.2** Password harus di-hash dengan **bcrypt** (cost factor 12); tidak ada jalur kode yang menyimpan password mentah.
- **NFR-1.3** Sistem harus membatasi percobaan login (5x/menit per NIP+IP), verifikasi 2FA (5x/menit per sesi), dan reset password mandiri (5x/menit per NIP+IP).
- **NFR-1.4** Sistem harus mencegah *mass assignment* dengan mendeklarasikan kolom fillable eksplisit pada seluruh model.
- **NFR-1.5** Seluruh output ke tampilan harus di-escape otomatis; output mentah hanya digunakan untuk konten yang dibuat sistem sendiri (bukan input pengguna).
- **NFR-1.6** Seluruh query database harus memakai parameterized query (Eloquent) untuk mencegah SQL Injection.
- **NFR-1.7** Seluruh form harus dilindungi CSRF token.
- **NFR-1.8** Sesi pengguna harus memakai cookie **HttpOnly** + **SameSite=Lax** dengan masa berlaku 120 menit.
- **NFR-1.9** Upload file harus dibatasi maksimal **5 MB** dan divalidasi tipenya (gambar/dokumen) sesuai konteks penggunaan.
- **NFR-1.10** Token penghubung akun Telegram harus acak (32 karakter), sekali pakai, dan tidak pernah dipakai sebagai kredensial login.
- **NFR-1.11** Halaman pengelolaan password & 2FA harus mewajibkan konfirmasi ulang password sebelum dapat diakses, walau sesi login masih berjalan.

### 2. Usability

- **NFR-2.1** Antarmuka harus responsif, dapat digunakan dengan baik di desktop (fokus Admin) maupun perangkat mobile (fokus Petugas Lapangan, mobile-first).
- **NFR-2.2** Sistem harus memberi umpan balik visual (toast) untuk setiap aksi sukses/gagal.
- **NFR-2.3** Sistem harus memvalidasi format input secara langsung/live (mis. RT hanya angka, nama hanya huruf, telepon hanya angka, deadline harus tanggal masa depan) sebelum form disubmit, agar kesalahan format terlihat sedini mungkin.
- **NFR-2.4** Notifikasi in-app yang punya tujuan halaman harus dapat diklik langsung untuk membuka halaman terkait sekaligus menandainya terbaca.

### 3. Performance Efficiency

- **NFR-3.1** Peta harus menampilkan seluruh titik rambu dengan warna yang dihitung di sisi client secara real-time, tanpa query tambahan per pin.
- **NFR-3.2** Laporan Bulanan dan Laporan Rambu harus berbagi satu sumber query filter yang sama agar angka yang ditampilkan selalu konsisten antar laporan.

### 4. Reliability

- **NFR-4.1** Kegagalan pengiriman notifikasi Telegram (mis. proses polling/queue tidak berjalan) tidak boleh menggagalkan atau menunda pencatatan notifikasi in-app.
- **NFR-4.2** Exception tak tertangani pada aplikasi harus tercatat otomatis ke System Error Log tanpa menghentikan proses bisnis lain yang tidak terkait.

### 5. Maintainability

- **NFR-5.1** Log teknis sistem (System Error Log) harus terpisah dari Audit Trail bisnis, agar keduanya bisa dibaca sesuai tujuannya masing-masing.
- **NFR-5.2** Sistem harus dibangun dengan arsitektur modular (Laravel MVC + komponen Livewire per fitur) agar tiap modul dapat dikembangkan dan diuji secara independen.

### 6. Compatibility

- **NFR-6.1** Sistem harus berjalan baik pada browser modern (Chrome, Firefox, Edge versi terbaru).

### 7. Scalability

- **NFR-7.1** Penambahan jumlah pengguna atau data rambu/SPK tidak boleh memerlukan perombakan arsitektur besar.
