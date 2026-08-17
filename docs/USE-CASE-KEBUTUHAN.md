# Use Case, Kebutuhan Fungsional, dan Kebutuhan Non-Fungsional Sistem MARLIN

## Pengantar

Dokumen ini disusun mengikuti format yang lazim dipakai pada draft PRD (Product Requirements Document) awal proyek ini, khususnya berkas `PRD PKL (1).docx` yang menjadi cikal bakal perencanaan sistem. Namun, penting untuk digarisbawahi bahwa isi dari dokumen ini bukan sekadar menyalin ulang rencana awal tersebut, melainkan diambil sepenuhnya dari **sistem yang sudah benar-benar dibangun dan berjalan** pada saat dokumen ini ditulis. Sumber kebenaran yang dipakai untuk menyusun setiap baris di dalam dokumen ini adalah [FITUR.md](FITUR.md), [ALUR-BISNIS.md](ALUR-BISNIS.md), [DAFTAR-AKTIVITAS.md](DAFTAR-AKTIVITAS.md), [KEAMANAN.md](KEAMANAN.md), dan [DATABASE.md](DATABASE.md), yang seluruhnya sudah diverifikasi langsung terhadap kode sumber yang sesungguhnya.

Kode referensi yang muncul pada kolom **Catatan**, seperti `US-xx`, `AS-xx`, dan `AU-xx`, merujuk langsung ke [DAFTAR-AKTIVITAS.md](DAFTAR-AKTIVITAS.md) untuk penjelasan yang lebih terperinci tentang setiap aktivitas yang bersangkutan.

Perbedaan antara dokumen ini dengan sebuah PRD murni terletak pada tujuannya. Sebuah PRD biasanya ditulis sebelum sistem dibangun, sebagai spesifikasi tentang apa yang *seharusnya* dibuat. Dokumen ini justru ditulis dari arah sebaliknya, menangkap apa yang *sudah* dibuat dan bagaimana perilakunya yang sesungguhnya, sehingga bisa dijadikan acuan pengujian, dokumentasi serah terima, atau bahan evaluasi kesesuaian antara sistem yang jadi dengan kebutuhan awal yang direncanakan.

---

## Daftar Isi

- [User Stories / Use Case](#user-stories--use-case)
- [Kebutuhan Fungsional](#kebutuhan-fungsional)
- [Kebutuhan Non-Fungsional](#kebutuhan-non-fungsional)

---

## User Stories / Use Case

Bagian ini menyajikan setiap kemampuan sistem dalam format user story, yaitu sebuah kalimat yang menjelaskan siapa pengguna yang membutuhkan sebuah kemampuan, apa kemampuan itu, dan mengapa mereka membutuhkannya. Format ini sengaja dipilih karena ia memaksa penulisnya untuk selalu berpikir dari sudut pandang manfaat bagi pengguna, bukan sekadar mendaftar fitur teknis tanpa konteks.

| Modul | Use Case | User Story | Prioritas | Aktor | Catatan |
|---|---|---|---|---|---|
| Otentikasi | Login Sistem | Sebagai pengguna terdaftar, saya ingin login memakai NIP dan kata sandi (serta kode 2FA apabila aktif), sehingga saya bisa mengakses sistem sesuai peran saya. | Tinggi | Admin & Petugas | Ref: US-01/AS-01 |
| Otentikasi | Reset Kata Sandi Mandiri | Sebagai pengguna yang lupa kata sandi, saya ingin menggantinya sendiri lewat wizard di halaman login (NIP + kata sandi lama), sehingga saya tidak perlu meminta bantuan admin. | Sedang | Admin & Petugas | Dibatasi 5 percobaan per menit. Ref: US-02 |
| Otentikasi | Mengelola Profil | Sebagai pengguna, saya ingin mengubah nama, nama panggilan, dan foto profil saya, sehingga data diri saya tetap akurat. | Rendah | Admin & Petugas | Ref: US-24 |
| Otentikasi | Mengelola Keamanan Akun | Sebagai pengguna, saya ingin mengganti kata sandi dan mengaktifkan/menonaktifkan 2FA saya sendiri, sehingga akun saya tetap aman. | Sedang | Admin & Petugas | Wajib konfirmasi ulang kata sandi. Ref: US-25 |
| Otentikasi | Mengelola Akun Pengguna | Sebagai admin, saya ingin menambah, mengubah, serta mengaktifkan/menonaktifkan akun petugas dan admin lain, sehingga akses ke sistem selalu terkendali dan terpusat. | Tinggi | Admin | Tidak ada registrasi publik. Ref: AS-17/AS-18 |
| Otentikasi | Logout | Sebagai pengguna, saya ingin keluar dari sistem, sehingga sesi saya berakhir dengan aman di perangkat yang mungkin dipakai bersama. | Rendah | Admin & Petugas | Ref: US-26/AS-23 |
| Dashboard | Melihat Dashboard Admin | Sebagai admin, saya ingin melihat ringkasan SPK aktif, rambu rusak, dan laporan menunggu validasi begitu login, sehingga saya tahu prioritas kerja hari itu. | Tinggi | Admin | Termasuk widget peta ringkas. Ref: AS-02 |
| Dashboard | Melihat Dashboard Petugas | Sebagai petugas, saya ingin melihat semua SPK aktif (bukan cuma yang saya ikuti) begitu login, sehingga saya bisa menemukan pekerjaan baru untuk diambil. | Tinggi | Petugas | Ref: US-03 |
| Master Data | Mengelola Jenis Rambu | Sebagai admin, saya ingin menambah, mengubah, dan menghapus jenis rambu beserta spesifikasinya, sehingga master data rambu selalu mutakhir. | Sedang | Admin | Ref: AS-15 |
| Master Data | Melihat Daftar Rambu | Sebagai pengguna, saya ingin melihat daftar seluruh rambu terdaftar beserta kondisinya, sehingga saya punya referensi aset yang ada. | Sedang | Admin & Petugas | Bersifat baca-saja bagi petugas. Ref: AS-16/US-19 |
| Peta Digital | Melihat Peta Rambu | Sebagai pengguna, saya ingin melihat seluruh titik rambu di peta dengan warna sesuai status terkini, sehingga saya cepat menilai kondisi lapangan tanpa membuka tiap rambu satu-satu. | Tinggi | Admin & Petugas | Ref: US-16 |
| Peta Digital | Melihat Kartu Info Pin | Sebagai pengguna, saya ingin mengklik pin di peta untuk melihat detail singkat rambu tersebut, sehingga saya tidak perlu berpindah halaman hanya untuk informasi dasar. | Sedang | Admin & Petugas | |
| Peta Digital | Mengunduh PDF Sebaran Rambu | Sebagai admin, saya ingin mengunduh cuplikan peta beserta tabel analitiknya sebagai PDF, sehingga saya bisa melaporkannya ke atasan. | Rendah | Admin | Ref: AS-21 |
| Peta Digital | Melapor Temuan dari Peta | Sebagai petugas, saya ingin melapor rambu rusak langsung dari kartu info pin di peta, sehingga saya tidak perlu berpindah ke halaman lain saat menemukannya di lapangan. | Sedang | Petugas | Jalan pintas dari US-12. Ref: US-17 |
| Penjadwalan (SPK) | Membuat Surat (SPK) | Sebagai admin, saya ingin membuat Surat Perintah Kerja dengan banyak baris rambu sekaligus (tiap baris pemasangan baru atau perbaikan, boleh dicampur), sehingga penugasan lapangan tercatat resmi dan terstruktur. | Tinggi | Admin | Urgensi terhitung otomatis. Ref: AS-03 |
| Penjadwalan (SPK) | Mengedit Surat (SPK) | Sebagai admin, saya ingin mengubah data SPK yang masih aktif (header maupun daftar rambunya), sehingga saya bisa mengoreksi kesalahan atau menyesuaikan kondisi terbaru tanpa membuat surat baru. | Sedang | Admin | Baris rambu yang statusnya sudah beranjak dari Belum/Urgent/Revisi hanya bisa dilihat, tidak lagi bisa diedit. Ref: AS-04 |
| Penjadwalan (SPK) | Membatalkan SPK | Sebagai admin, saya ingin membatalkan seluruh SPK yang tidak jadi dikerjakan, sehingga statusnya jelas tanpa menghapus riwayatnya. | Sedang | Admin | Tidak pernah dihapus secara permanen. Ref: AS-05 |
| Penjadwalan (SPK) | Membatalkan/Menghapus Satu Rambu | Sebagai admin, saya ingin membatalkan atau menghapus satu baris rambu dalam SPK tanpa mengganggu rambu lain, sehingga koreksi kecil tidak perlu membatalkan seluruh surat. | Rendah | Admin | Ref: AS-06/AS-07 |
| Penjadwalan (SPK) | Melihat Daftar Surat & Riwayat SPK | Sebagai admin, saya ingin melihat daftar SPK aktif terpisah dari arsip SPK selesai/dibatalkan, sehingga saya fokus ke pekerjaan yang masih berjalan. | Tinggi | Admin | Riwayat SPK dilengkapi filter rentang tanggal. Ref: AS-08 |
| Penjadwalan (SPK) | Mendaftarkan Tim | Sebagai petugas, saya ingin mendaftarkan diri sebagai perwakilan beserta rekan setim ke sebuah SPK, sehingga tim saya resmi bertanggung jawab mengerjakannya. | Tinggi | Petugas | Tidak bisa dibatalkan lewat sistem. Ref: US-05 |
| Penjadwalan (SPK) | Menambah/Menghapus Anggota Tim | Sebagai perwakilan tim, saya ingin menambah atau menghapus anggota tim non-perwakilan di kemudian hari, sehingga susunan tim tetap akurat kalau ada perubahan. | Rendah | Petugas | Ref: US-06/US-27 |
| Penjadwalan (SPK) | Mengunduh Surat Pengantar | Sebagai admin atau anggota tim, saya ingin mengunduh Surat Pengantar dalam bentuk PDF, sehingga ada dokumen resmi yang bisa dibawa ke lapangan. | Sedang | Admin & Petugas | Ref: US-15 |
| Penjadwalan (SPK) | Melihat Jadwal Tugas | Sebagai petugas, saya ingin melihat SPK yang sedang saya kerjakan dan riwayat pekerjaan saya, sehingga saya bisa memantau progres kerja tim saya. | Sedang | Petugas | Riwayat dilengkapi filter rentang tanggal, kosong berarti seluruh riwayat. Ref: US-13/US-14 |
| Eksekusi Lapangan | Mengisi Laporan Pengerjaan | Sebagai perwakilan tim, saya ingin mengirim laporan pengerjaan (foto sesudah + GPS wajib) untuk sebuah rambu, sehingga admin punya bukti pekerjaan sudah selesai untuk divalidasi. | Tinggi | Petugas | Hanya perwakilan. Ref: US-09 |
| Eksekusi Lapangan | Mengisi Form Kendala | Sebagai perwakilan tim, saya ingin melaporkan kendala (foto + alasan wajib) kalau pekerjaan tidak bisa dilanjutkan, sehingga admin tahu status sebenarnya di lapangan. | Tinggi | Petugas | Ref: US-07 |
| Eksekusi Lapangan | Menukar Kendala/Laporan | Sebagai perwakilan tim, saya ingin mengedit atau menukar jenis laporan (kendala ke selesai, atau sebaliknya) sebelum Laporan Akhir diajukan, sehingga saya bisa mengoreksi kalau situasi lapangan berubah. | Rendah | Petugas | Ref: US-08/US-10 |
| Eksekusi Lapangan | Mengajukan Laporan Akhir | Sebagai perwakilan tim, saya ingin mengajukan Laporan Akhir setelah seluruh rambu dalam SPK tertangani, sehingga SPK masuk antrean validasi admin dalam satu batch, bukan satu-satu. | Tinggi | Petugas | Ref: US-11 |
| Validasi & Persetujuan | Memvalidasi Laporan Pengerjaan | Sebagai admin, saya ingin meninjau dan menerima/menolak tiap rambu dalam SPK yang sudah mengajukan Laporan Akhir, sehingga hanya pekerjaan yang benar-benar sesuai standar yang ditandai selesai. | Tinggi | Admin | Kendala tidak bisa diterima. Ref: AS-09 |
| Validasi & Persetujuan | Memperpanjang Deadline Saat Menolak | Sebagai admin, saya ingin memberi kelonggaran deadline SPK langsung dari form penolakan, sehingga petugas punya waktu wajar untuk merevisi tanpa saya harus membuka halaman terpisah. | Rendah | Admin | Ref: AS-09b |
| Temuan Kondisi | Melapor Temuan Kondisi | Sebagai siapa saja yang login, saya ingin melaporkan rambu yang saya temukan rusak di lapangan (di luar SPK aktif manapun), sehingga kerusakan segera tercatat dan pin peta berubah warna. | Sedang | Admin & Petugas | Ref: US-12 |
| Temuan Kondisi | Menindaklanjuti Temuan | Sebagai admin, saya ingin membuat SPK perbaikan langsung dari sebuah temuan kondisi, atau menolaknya kalau tidak perlu ditindaklanjuti, sehingga antrean temuan tidak menumpuk tanpa kejelasan. | Sedang | Admin | Ref: AS-11/AS-12 |
| Riwayat & Pelaporan | Membuat Laporan Bulanan/Rambu | Sebagai admin, saya ingin mengekspor rekap pekerjaan rambu ke PDF dengan filter tanggal/jenis/status, sehingga saya punya bahan laporan ke atasan. | Sedang | Admin | Ref: AS-13/AS-14 |
| Riwayat & Pelaporan | Melihat Detail Rambu | Sebagai pengguna, saya ingin melihat riwayat foto, kondisi, dan pekerjaan satu rambu tertentu, sehingga saya paham histori lengkapnya sebelum mengambil keputusan. | Sedang | Admin & Petugas | Ref: US-18 |
| Notifikasi | Melihat Notifikasi In-App | Sebagai pengguna, saya ingin melihat dan membaca notifikasi lewat ikon lonceng, sehingga saya tidak ketinggalan peristiwa penting yang relevan dengan peran saya. | Tinggi | Admin & Petugas | Ref: US-20 |
| Notifikasi | Menghubungkan Akun Telegram | Sebagai pengguna, saya ingin menghubungkan akun saya ke bot Telegram, sehingga saya tetap dapat notifikasi walau tidak sedang membuka sistem. | Rendah | Admin & Petugas | Ref: US-21/US-22 |
| Audit | Melihat Riwayat Aktivitas (Seluruh Sistem) | Sebagai admin, saya ingin melihat seluruh jejak aksi bisnis kunci lintas pengguna, sehingga saya bisa menelusuri siapa melakukan apa kalau terjadi sengketa atau kesalahan. | Sedang | Admin | Dilengkapi filter pengguna dan rentang tanggal. Ref: AS-19 |
| Audit | Melihat Aktivitas Sendiri | Sebagai petugas, saya ingin melihat riwayat aktivitas yang saya lakukan sendiri, sehingga saya punya bukti kerja saya sendiri. | Rendah | Petugas | Ref: US-23 |

---

## Kebutuhan Fungsional

Bagian ini merumuskan setiap kemampuan sistem sebagai sebuah pernyataan kebutuhan yang formal, disusun per modul, dan diberi kode `FR-` (Functional Requirement) yang bisa dirujuk secara konsisten dari dokumen pengujian atau dokumen lain yang membutuhkannya.

### 1. Modul Autentikasi & Manajemen Akun

Sistem harus menyediakan login berbasis **NIP dan kata sandi** (`FR-1.1`), bukan alamat surel, dengan verifikasi kredensial kustom yang sekaligus memeriksa status aktif atau tidaknya sebuah akun. Sistem juga harus mendukung **Two-Factor Authentication (2FA)** yang bersifat opsional per akun (`FR-1.2`), yang wajib dikonfirmasi ulang dengan kata sandi sebelum bisa diaktifkan atau diubah.

Untuk mencegah percobaan menebak kredensial secara berulang, sistem harus membatasi percobaan login gagal maksimal **lima kali per menit** per kombinasi NIP dan alamat IP, serta verifikasi kode 2FA maksimal lima kali per menit per sesi login (`FR-1.3`). Sistem juga harus menyediakan wizard **reset kata sandi mandiri** di halaman login, yang mensyaratkan NIP dan kata sandi lama, dengan pembatasan yang sama sebanyak lima percobaan per menit per kombinasi NIP dan IP (`FR-1.4`).

Sistem harus menerapkan **kontrol akses berbasis peran (RBAC)** untuk membedakan Admin dan Petugas Lapangan (`FR-1.5`). Percobaan mengakses halaman peran lain harus dialihkan ke dashboard sesuai peran pengguna, bukan menampilkan halaman kesalahan. Pembuatan akun harus dibatasi **hanya oleh Admin** (`FR-1.6`); tidak boleh ada halaman registrasi mandiri atau publik. Admin harus dapat menambah, mengubah data, serta **mengaktifkan atau menonaktifkan** akun Petugas dan Admin lain (`FR-1.7`), dan akun yang dinonaktifkan tidak boleh bisa login lagi.

Setiap pengguna harus dapat mengelola profil mereka sendiri, mencakup nama, nama panggilan, dan foto (`FR-1.8`), serta mengganti kata sandi dan mengelola 2FA milik mereka sendiri (`FR-1.9`), dengan konfirmasi ulang kata sandi sebelum halaman Keamanan bisa diakses. Terakhir, sistem harus menyediakan mekanisme logout yang mengakhiri sesi pengguna dengan aman (`FR-1.10`).

### 2. Modul Dashboard

Dashboard Admin harus menampilkan ringkasan jumlah SPK aktif, rambu berkondisi rusak, dan laporan yang menunggu validasi (`FR-2.1`), dilengkapi dengan widget peta ringkas yang memiliki filternya sendiri (jenis rambu, tingkat urgensi, rentang tanggal) beserta tombol unduh laporan sebaran rambu dalam bentuk PDF (`FR-2.2`). Dashboard Petugas, yang dikenal sebagai "Daftar Surat Aktif", harus menampilkan **seluruh** SPK berstatus aktif, bukan hanya yang sudah diikuti timnya (`FR-2.3`), agar petugas bisa menemukan pekerjaan baru, dan harus menampilkan ringkasan jumlah tugas aktif, tugas dalam progres, tugas mendekati tenggat waktu, dan tugas selesai bulan berjalan milik timnya sendiri (`FR-2.4`).

### 3. Modul Manajemen Data Rambu (Master Data)

Admin harus dapat mengelola data **Jenis Rambu** (`FR-3.1`), mencakup aksi tambah, ubah, dan hapus, beserta nama, spesifikasi standar, gambar referensi, dan bentuk ikon di peta. Sistem harus menyediakan daftar seluruh **Rambu** terdaftar, yang dapat difilter berdasarkan kondisi dan wilayah (`FR-3.2`), dapat diakses oleh admin dengan kemampuan kelola penuh, maupun petugas dengan akses baca-saja. Sistem juga harus menampilkan kondisi rambu yang belum terpasang sebagai **"N/A"**, bukan "Baik" (`FR-3.3`), karena rambu semacam itu belum menjadi objek fisik yang nyata.

### 4. Modul Peta Digital Interaktif

Sistem harus menampilkan seluruh titik rambu sebagai pin pada peta interaktif berbasis Leaflet dan OpenStreetMap (`FR-4.1`), dengan warna pin yang mencerminkan status pekerjaan atau kondisi terkini secara real-time, tanpa perlu memuat ulang halaman atau melakukan kueri tambahan. Titik rambu baru pada peta hanya boleh muncul lewat proses pembuatan SPK (`FR-4.2`), bukan lewat input manual langsung di atas peta. Mengklik sebuah pin harus membuka kartu informasi berisi foto, jenis rambu, lokasi, koordinat, dan status, disertai tombol navigasi menuju Google Maps dan halaman Detail Rambu (`FR-4.3`). Sistem harus menyediakan tombol pelaporan Temuan Kondisi langsung dari kartu informasi pin, khusus bagi petugas (`FR-4.4`). Peta juga harus mendukung filter berdasarkan jenis rambu, tingkat urgensi, dan rentang tanggal (`FR-4.5`).

### 5. Modul Penjadwalan & Penugasan (SPK)

Admin harus dapat membuat SPK dengan banyak baris rambu sekaligus dalam satu surat, dan setiap baris memilih sendiri jenis pekerjaannya, Pemasangan Baru atau Perbaikan, sehingga satu SPK boleh mencampur keduanya (`FR-5.1`). Sistem harus menghitung **urgensi SPK secara otomatis** berdasarkan sisa hari ke tenggat waktu: ditandai Prioritas atau sisa dua hari atau kurang menghasilkan Tinggi, sisa tujuh hari atau kurang menghasilkan Sedang, dan selebihnya menghasilkan Rendah (`FR-5.2`). Untuk SPK berstatus Aktif, urgensi harus dihitung ulang secara **live** setiap kali ditampilkan, bukan hanya dibaca dari kolom yang tersimpan.

Sistem harus menolak Deadline SPK yang bukan tanggal **setelah** hari ini, tidak boleh diisi dengan hari ini sendiri atau tanggal yang sudah lewat (`FR-5.3`). Admin harus dapat mengedit data SPK, baik header maupun daftar rambu, selama statusnya masih **Aktif** (`FR-5.4`), namun khusus untuk baris rambu, kemampuan mengedit ini **hanya berlaku selama status baris tersebut masih Belum, Urgent, atau Revisi**; baris yang sudah beranjak ke Tertunda, Menunggu Validasi, atau Selesai harus ditampilkan sebagai ringkasan baca-saja, dan penguncian ini harus diperiksa ulang di sisi server, tidak cukup hanya disembunyikan di tampilan.

Admin harus dapat membatalkan seluruh SPK (statusnya berubah menjadi Dibatalkan, **tidak pernah dihapus secara permanen**), dengan seluruh rambu yang belum selesai ikut ditandai Batal (`FR-5.5`). Admin juga harus dapat membatalkan satu baris rambu dalam SPK secara terpisah, wajib mengisi alasan, tanpa memengaruhi rambu lain dalam SPK yang sama maupun status SPK-nya, dan hanya tersedia selama baris tersebut masih berstatus Belum, Urgent, atau Revisi (`FR-5.6`). Admin harus dapat menghapus permanen satu baris `rambu_pasang`, hanya apabila statusnya masih Belum atau Batal dan belum ada kendala maupun laporan sama sekali (`FR-5.7`).

Sistem harus menampilkan Daftar Surat (SPK aktif) terpisah dari Riwayat SPK (SPK Selesai/Dibatalkan), masing-masing dengan pencarian dan filter tersendiri, termasuk filter rentang tanggal pada Riwayat SPK (`FR-5.8`). Petugas, sebagai perwakilan, harus dapat mendaftarkan diri beserta rekan setim ke sebuah SPK dalam satu aksi, dan menambah atau menghapus anggota tim non-perwakilan di kemudian hari (`FR-5.9`). Sistem harus menyediakan unduh Surat Pengantar dalam bentuk PDF, dibatasi hanya untuk admin atau anggota tim SPK terkait (`FR-5.10`). Saat SPK baru yang ditandai Prioritas dibuat, sistem harus otomatis memundurkan tenggat waktu SPK aktif non-prioritas lain yang relevan, bersifat maksimal dan tidak akumulatif, disertai notifikasi kepada pemiliknya (`FR-5.11`).

### 6. Modul Eksekusi Lapangan (Antarmuka Petugas)

Hanya **perwakilan tim** yang boleh mengisi Form Laporan Pengerjaan, Form Kendala, dan mengajukan Laporan Akhir (`FR-6.1`). Form Laporan Pengerjaan harus mewajibkan foto sesudah dan koordinat GPS, dan sistem harus menolak pengiriman yang tidak menyertakan keduanya, disertai pesan kesalahan yang jelas (`FR-6.2`). Form Kendala harus mewajibkan foto dan alasan kendala (`FR-6.3`).

Mengisi Form Laporan Pengerjaan harus mengubah status rambu menjadi **Menunggu Validasi**; mengisi Form Kendala harus mengubah status menjadi **Tertunda** (`FR-6.4`). Petugas, sebagai perwakilan, harus dapat mengedit ulang atau menukar jenis laporan (dari Kendala menjadi Laporan Pengerjaan, atau sebaliknya) untuk rambu yang sama, selama SPK belum mengajukan Laporan Akhir (`FR-6.5`).

Sistem harus mengizinkan tombol **"Ajukan Laporan Akhir"** muncul hanya apabila seluruh rambu dalam SPK sudah berstatus Tertunda, Menunggu Validasi, atau Selesai (dengan minimal satu yang baru), dan tidak ada satu pun yang masih Belum atau Revisi (`FR-6.6`). Sistem harus menyediakan pelaporan **Temuan Kondisi** yang independen dari SPK aktif mana pun, dapat dilakukan oleh siapa saja yang login, bukan hanya perwakilan, dengan foto yang wajib disertakan (`FR-6.7`). Sistem harus menyediakan tombol navigasi eksternal menuju Google Maps dari Detail SPK maupun kartu peta (`FR-6.8`). Sistem harus menampilkan **Riwayat Pekerjaan Saya** dan **SPK Sedang Dikerjakan** bagi petugas, dilengkapi filter rentang tanggal (`FR-6.9`).

### 7. Modul Validasi & Persetujuan (Admin)

Sistem harus menampilkan antrean **Validasi Pengerjaan** hanya untuk SPK yang sudah mengajukan Laporan Akhir (`FR-7.1`), karena validasi dilakukan per-batch per-SPK, bukan per-rambu begitu rambu itu dikirimkan. Admin harus dapat menandai setiap rambu sebagai diterima atau ditolak. Rambu berstatus **Tertunda** (karena kendala) **tidak dapat** diterima atau dicentang, dipaksa oleh sistem di sisi server untuk tetap masuk jalur revisi apa pun input yang dikirim dari klien (`FR-7.2`).

Menolak sebuah rambu wajib disertai **catatan penolakan**, mengubah statusnya menjadi **Revisi**; rambu lain dalam SPK yang sama tidak boleh terpengaruh (`FR-7.3`). Menerima sebuah rambu mengubah statusnya menjadi **Selesai**, sekaligus memperbarui `sudah_terpasang` untuk Pemasangan Baru, atau `kondisi_terkini` untuk Perbaikan, pada rambu terkait (`FR-7.4`).

Admin harus dapat memperpanjang tenggat waktu SPK langsung dari form penolakan validasi, bersifat opsional lewat kotak centang "beri kelonggaran", sebagai satu transaksi dengan penolakan rambunya, tercatat di Audit Log dan memicu notifikasi kepada seluruh tim (`FR-7.5`). Sistem harus mereset gerbang Laporan Akhir (`laporan_akhir_diajukan_at` menjadi kosong) setiap kali admin memproses validasi, apa pun hasilnya, agar SPK yang masih memiliki rambu Revisi otomatis keluar dari antrean sampai diajukan ulang (`FR-7.6`). Sistem harus otomatis mengubah status SPK menjadi **Selesai** ketika seluruh rambu di dalamnya sudah Selesai atau Batal, dan mencatat waktu selesainya untuk keperluan analitik Durasi Pengerjaan dan Selisih dari Deadline (`FR-7.7`).

### 8. Modul Temuan Kondisi

Sistem harus menyediakan antrean **Temuan Lapangan** bagi Admin, berisi laporan kondisi rusak yang belum ditindaklanjuti (`FR-8.1`). Admin harus dapat menindaklanjuti temuan dengan membuat SPK perbaikan baru, di mana foto temuan otomatis dibawa sebagai foto survei rambu di SPK baru, **atau** menolak temuan tersebut, dengan notifikasi kepada pelapor pada kedua kasus (`FR-8.2`).

### 9. Modul Riwayat & Pelaporan

Sistem harus menyediakan **Laporan Bulanan** dan **Laporan Rambu**, keduanya dengan filter rentang tanggal, jenis rambu, dan status, dapat diekspor ke PDF, dan harus berbagi sumber query yang sama agar angkanya selalu konsisten satu sama lain (`FR-9.1`). Sistem harus menyediakan **Riwayat SPK**, sebuah arsip SPK Selesai atau Dibatalkan, dilengkapi filter rentang tanggal, termasuk Durasi Pengerjaan dan Selisih dari Deadline untuk SPK berstatus Selesai (`FR-9.2`). Sistem harus menampilkan riwayat foto dan kondisi per rambu pada halaman **Detail Rambu**, dengan foto utama yang otomatis mengikuti kondisi terkini (foto sesudah terbaru menang atas foto survei) (`FR-9.3`).

### 10. Modul Notifikasi

Sistem harus mengirimkan notifikasi in-app untuk peristiwa kunci, seperti SPK baru tersedia, Laporan Akhir masuk, laporan diterima atau ditolak, dan temuan kondisi, sesuai peran penerimanya (`FR-10.1`). Sistem harus menampilkan lencana jumlah notifikasi yang belum dibaca (ditampilkan maksimal sebagai "9+") pada ikon lonceng di header (`FR-10.2`). Sistem harus mendukung penghubungan akun pengguna ke **Telegram** lewat token sekali pakai, sehingga notifikasi yang sama juga terkirim sebagai pesan chat, termasuk foto apabila notifikasinya memiliki lampiran (`FR-10.3`). Notifikasi Telegram harus tetap terkirim meskipun URL tujuan pada notifikasinya tidak bisa diakses secara publik, misalnya pada domain pengembangan lokal, dengan cara menghilangkan tombol tautan alih-alih membatalkan seluruh pesan (`FR-10.4`). Pengguna harus dapat memutuskan hubungan akun Telegram mereka kapan saja mereka inginkan (`FR-10.5`).

### 11. Modul Audit Trail & Log Sistem

Sistem harus mencatat setiap aksi bisnis penting, seperti SPK dibuat, diedit, atau dibatalkan, laporan dikirim, diterima, atau ditolak, kendala diajukan, dan tenggat waktu diperpanjang, ke dalam **Audit Log** secara append-only (`FR-11.1`). Admin harus dapat melihat seluruh Audit Log lintas pengguna, sementara Petugas hanya dapat melihat aktivitas yang dilakukannya **sendiri** (`FR-11.2`). Sistem harus mencatat exception tak tertangani (level 500 ke atas) secara otomatis ke **System Error Log**, dapat dilihat Admin tanpa perlu akses server atau berkas log secara langsung (`FR-11.3`).

---

## Kebutuhan Non-Fungsional

Berbeda dengan kebutuhan fungsional yang menjelaskan *apa* yang harus bisa dilakukan sistem, kebutuhan non-fungsional menjelaskan *seberapa baik* sistem harus melakukannya, mencakup aspek-aspek seperti keamanan, kemudahan pemakaian, performa, dan keandalan.

### 1. Security (Keamanan)

Sistem harus menerapkan RBAC (Admin/Petugas) di seluruh grup rute, dengan pengalihan ke dashboard sesuai peran saat terjadi percobaan akses lintas peran (`NFR-1.1`). Kata sandi harus di-hash dengan **bcrypt** cost factor 12, tanpa satu pun jalur kode yang menyimpan kata sandi mentah (`NFR-1.2`). Sistem harus membatasi percobaan login (lima kali per menit per NIP dan IP), verifikasi 2FA (lima kali per menit per sesi), dan reset kata sandi mandiri (lima kali per menit per NIP dan IP) (`NFR-1.3`).

Sistem harus mencegah *mass assignment* dengan mendeklarasikan kolom fillable secara eksplisit pada seluruh model (`NFR-1.4`). Seluruh output ke tampilan harus di-escape secara otomatis; output mentah hanya boleh digunakan untuk konten yang dibuat sistem sendiri, bukan berasal dari input pengguna (`NFR-1.5`). Seluruh kueri basis data harus memakai parameterized query lewat Eloquent untuk mencegah SQL Injection (`NFR-1.6`). Seluruh formulir harus dilindungi token CSRF (`NFR-1.7`). Sesi pengguna harus memakai cookie **HttpOnly** dan **SameSite=Lax** dengan masa berlaku 120 menit (`NFR-1.8`).

Unggahan berkas harus dibatasi maksimal **5 MB** dan divalidasi tipenya, baik gambar maupun dokumen, sesuai konteks penggunaannya masing-masing (`NFR-1.9`). Token penghubung akun Telegram harus bersifat acak (32 karakter), sekali pakai, dan tidak pernah dipakai sebagai kredensial login (`NFR-1.10`). Halaman pengelolaan kata sandi dan 2FA harus mewajibkan konfirmasi ulang kata sandi sebelum bisa diakses, walaupun sesi login masih berjalan (`NFR-1.11`).

### 2. Usability (Kemudahan Pemakaian)

Antarmuka harus responsif, dapat digunakan dengan baik baik di desktop (fokus penggunaan Admin) maupun perangkat mobile (fokus penggunaan Petugas Lapangan, dirancang mobile-first) (`NFR-2.1`). Sistem harus memberi umpan balik visual berupa notifikasi toast untuk setiap aksi yang sukses maupun gagal (`NFR-2.2`). Sistem harus memvalidasi format input secara langsung atau live (misalnya RT hanya boleh angka, nama hanya boleh huruf, telepon hanya boleh angka, deadline harus tanggal di masa depan) sebelum formulir dikirimkan, agar kesalahan format terlihat sedini mungkin (`NFR-2.3`). Notifikasi in-app yang punya tujuan halaman harus dapat diklik langsung untuk membuka halaman terkait sekaligus menandainya terbaca (`NFR-2.4`).

### 3. Performance Efficiency (Efisiensi Performa)

Peta harus menampilkan seluruh titik rambu dengan warna yang dihitung di sisi klien secara real-time, tanpa memerlukan kueri tambahan per pin (`NFR-3.1`). Laporan Bulanan dan Laporan Rambu harus berbagi satu sumber query filter yang sama agar angka yang ditampilkan selalu konsisten antar kedua laporan tersebut (`NFR-3.2`).

### 4. Reliability (Keandalan)

Kegagalan pengiriman notifikasi Telegram, misalnya karena proses polling atau queue tidak berjalan, tidak boleh menggagalkan atau menunda pencatatan notifikasi in-app (`NFR-4.1`). Exception tak tertangani pada aplikasi harus tercatat secara otomatis ke System Error Log tanpa menghentikan proses bisnis lain yang tidak terkait (`NFR-4.2`).

### 5. Maintainability (Kemudahan Pemeliharaan)

Log teknis sistem (System Error Log) harus terpisah dari Audit Trail bisnis, agar keduanya bisa dibaca sesuai tujuannya masing-masing (`NFR-5.1`). Sistem harus dibangun dengan arsitektur modular, memakai pola Laravel MVC dipadukan dengan komponen Livewire per fitur, agar setiap modul dapat dikembangkan dan diuji secara independen (`NFR-5.2`).

### 6. Compatibility (Kompatibilitas)

Sistem harus berjalan dengan baik pada peramban modern, mencakup Chrome, Firefox, dan Edge versi terbaru (`NFR-6.1`).

### 7. Scalability (Skalabilitas)

Penambahan jumlah pengguna atau data rambu maupun SPK tidak boleh memerlukan perombakan arsitektur secara besar-besaran (`NFR-7.1`).
