# Struktur Bab dan Subbab: Buku Panduan Pengguna Sistem MARLIN

## Pengantar

Dokumen ini merupakan rancangan struktur bab dan subbab untuk penyusunan sebuah Buku Panduan Pengguna (*user manual*) bagi Sistem MARLIN. Penting untuk dipahami sejak awal bahwa dokumen ini adalah sebuah **kerangka atau outline**, bukan isi lengkap dari buku panduannya itu sendiri. Setiap subbab di bawah ini diberi catatan singkat yang menjelaskan apa yang perlu ditulis di dalamnya, sehingga penulis buku panduan yang sesungguhnya, baik itu tim dokumentasi, mahasiswa yang sedang menyusun laporan, atau siapa pun yang ditugaskan menulisnya, memiliki peta yang jelas tentang cakupan dan urutan setiap bagian sebelum mulai menulis kontennya secara detail.

Struktur bab ini disusun dengan sengaja mengikuti urutan menu sidebar yang benar-benar ada di dalam aplikasi, dipisahkan per peran (Admin dan Petugas Lapangan). Alasannya sederhana namun penting: dengan mengikuti urutan yang sama persis dengan yang ada di sistem, seorang pembaca bisa membuka buku panduan ini sambil mengklik menu-menu yang sama urutannya secara langsung di aplikasi, membuat proses belajar menjadi jauh lebih intuitif ketimbang harus melompat-lompat antara buku panduan yang disusun secara acak dengan tampilan aplikasi yang urutannya berbeda.

Sumber kebenaran fitur yang dipakai untuk menyusun kerangka ini adalah [FITUR.md](FITUR.md), [HALAMAN.md](HALAMAN.md), [ALUR-BISNIS.md](ALUR-BISNIS.md), dan [KEAMANAN.md](KEAMANAN.md), yang seluruhnya sudah diverifikasi langsung terhadap kode sumber yang sesungguhnya berjalan pada sistem.

---

## BAB I: PENDAHULUAN

Bab pembuka ini bertujuan mengorientasikan pembaca sebelum mereka mulai berinteraksi dengan sistem secara langsung, menjawab pertanyaan-pertanyaan dasar seperti "apa ini," "untuk siapa," dan "apa yang saya butuhkan untuk mulai menggunakannya."

### 1.1 Tentang Sistem MARLIN

Bagian ini memberikan gambaran umum tentang Sistem MARLIN sebagai sistem manajemen rambu lalu lintas yang dibangun untuk Dinas Perhubungan Kota Banjarmasin, menggantikan koordinasi manual yang sebelumnya banyak berlangsung lewat percakapan WhatsApp antara pengawas (Admin) dan petugas lapangan. Bagian ini sebaiknya menjelaskan latar belakang masalah yang mendorong pembangunan sistem, dan bagaimana sistem ini menjawabnya.

### 1.2 Tujuan Buku Panduan

Bagian ini menjelaskan bahwa tujuan buku panduan adalah membantu pengguna baru, baik itu Admin maupun Petugas, memahami cara menjalankan tugas mereka lewat sistem, mulai dari proses login sampai menyelesaikan sebuah pekerjaan dari awal hingga akhir.

### 1.3 Peran Pengguna

Bagian ini menjelaskan dua peran yang ada di dalam sistem: **Admin**, yang bertanggung jawab mengelola SPK, memvalidasi laporan, dan mengelola master data; serta **Petugas Lapangan**, yang bertanggung jawab mengerjakan SPK secara fisik dan melaporkan hasil kerjanya.

### 1.4 Istilah dan Singkatan (Glosarium Awal)

Bagian ini memperkenalkan istilah-istilah kunci yang akan sering muncul di sepanjang buku panduan, seperti SPK, Rambu Pasang, Perwakilan, Laporan Akhir, dan Urgensi, sebagai pengenalan awal yang ringkas. Daftar istilah yang lebih lengkap dan terperinci disediakan pada Lampiran (Bab VII).

### 1.5 Kebutuhan Perangkat

Bagian ini mencantumkan kebutuhan teknis minimal untuk menggunakan sistem: peramban modern seperti Chrome, Firefox, atau Edge, koneksi internet yang stabil, dan bagi Petugas Lapangan secara khusus, sebuah telepon pintar dengan GPS dan kamera yang aktif, karena kedua fitur ini dibutuhkan langsung saat mengisi laporan pengerjaan di lapangan.

---

## BAB II: MEMULAI PENGGUNAAN SISTEM

Bab ini menuntun pembaca melewati proses paling awal sebelum mereka bisa mulai bekerja dengan sistem sesungguhnya, yaitu masuk dan mengenali antarmuka dasarnya.

### 2.1 Mengakses Sistem

Bagian ini mencantumkan alamat atau URL sistem, disertai catatan penting bahwa akun dibuat oleh Admin secara terpusat, tidak ada jalur pendaftaran mandiri yang bisa diakses siapa saja.

### 2.2 Login

Bagian ini menjelaskan langkah-langkah login memakai NIP dan kata sandi, sekaligus menegaskan bahwa sistem ini tidak menggunakan alamat surel sebagai identitas login, berbeda dari kebanyakan aplikasi pada umumnya.

### 2.3 Verifikasi Dua Langkah (2FA)

Bagian ini menjelaskan langkah memasukkan kode verifikasi bagi akun yang sudah mengaktifkan Autentikasi Dua Faktor, mencakup dari mana kode tersebut didapatkan dan apa yang terjadi apabila kode yang dimasukkan salah.

### 2.4 Lupa Kata Sandi

Bagian ini menjelaskan langkah-langkah mereset kata sandi secara mandiri lewat wizard yang tersedia di halaman login, yang mensyaratkan NIP beserta kata sandi lama sebagai verifikasi identitas.

### 2.5 Mengenal Tampilan Utama

Bagian ini menjelaskan bagian-bagian penting dari antarmuka sistem: sidebar di sisi kiri (menu yang ditampilkan berbeda tergantung peran pengguna), header di bagian atas (berisi ikon notifikasi dan menu profil), serta area konten utama di tengah layar tempat setiap halaman ditampilkan.

### 2.6 Logout

Bagian ini menjelaskan cara keluar dari sistem dengan aman, sebuah langkah sederhana namun penting untuk diketahui, terutama bagi pengguna yang mengakses sistem lewat perangkat yang dipakai bersama.

---

## BAB III: PANDUAN PENGGUNAAN UNTUK ADMIN

Bab ini adalah bab terpanjang bagi peran Admin, disusun mengikuti urutan grup menu sidebar admin secara berurutan, dari Dashboard sampai ke pengaturan lainnya.

### 3.1 Dashboard

Bagian ini menjelaskan ringkasan SPK aktif, rambu rusak, laporan yang menunggu validasi, dan widget peta ringkas yang tersedia pada halaman ini.

### 3.2 Peta

Bagian ini menjelaskan cara melihat sebaran rambu di peta interaktif, cara memakai filter yang tersedia, cara membaca arti setiap warna pin, dan cara mengunduh laporan sebaran rambu dalam bentuk PDF.

### 3.3 Pengerjaan

Subbab ini terbagi menjadi beberapa bagian yang lebih kecil, mengikuti urutan menu Pengerjaan di sidebar.

**3.3.1 Daftar Surat.** Bagian ini menjelaskan cara melihat daftar SPK yang sedang aktif, cara mencari, dan cara memfilternya.

**3.3.2 Buat Surat.** Bagian ini menjelaskan langkah lengkap membuat sebuah SPK baru: memilih jenis pekerjaan per baris rambu (pemasangan baru atau perbaikan), mengisi alamat, mengisi tenggat waktu, menyusun daftar rambu, dan mengunggah berkas referensi apabila ada.

**3.3.3 Mengedit dan Membatalkan Surat.** Bagian ini menjelaskan cara mengubah data SPK yang masih aktif, serta cara membatalkan seluruh SPK atau hanya satu rambu saja beserta alasannya. Penting untuk ditekankan pada bagian ini bahwa kemampuan mengedit sebuah baris rambu hanya berlaku selama baris tersebut belum pernah dilaporkan atau divalidasi; baris yang sudah tertunda, menunggu validasi, atau selesai akan ditampilkan sebagai ringkasan yang tidak lagi bisa diubah.

**3.3.4 Validasi Laporan.** Bagian ini menjelaskan cara meninjau laporan yang masuk dari petugas, cara menerima atau menolak per rambu, dan opsi memberi kelonggaran tenggat waktu.

**3.3.5 Temuan Lapangan.** Bagian ini menjelaskan cara meninjau laporan kondisi rusak dari petugas, cara menindaklanjutinya menjadi sebuah SPK perbaikan, atau cara menolaknya apabila memang tidak perlu ditindaklanjuti.

### 3.4 Rambu

**3.4.1 Daftar Rambu.** Bagian ini menjelaskan cara melihat seluruh rambu yang terdaftar beserta kondisinya, dan cara memfilternya berdasarkan wilayah atau kondisi.

**3.4.2 Jenis Rambu.** Bagian ini menjelaskan cara mengelola master data jenis rambu, mencakup aksi tambah, ubah, dan hapus, beserta spesifikasi dan gambar referensinya.

### 3.5 Riwayat dan Laporan

**3.5.1 Riwayat SPK.** Bagian ini menjelaskan cara melihat arsip SPK yang sudah selesai atau dibatalkan, termasuk cara memakai filter rentang tanggal yang tersedia pada halaman ini.

**3.5.2 Riwayat Aktivitas.** Bagian ini menjelaskan cara melihat jejak aksi bisnis penting seluruh pengguna (audit log), termasuk filter berdasarkan jenis aksi, pengguna tertentu, dan rentang tanggal.

**3.5.3 Laporan Bulanan.** Bagian ini menjelaskan cara membuat dan mengunduh rekap pekerjaan rambu per rentang tanggal dalam bentuk PDF.

**3.5.4 Laporan Rambu.** Bagian ini menjelaskan cara membuat dan mengunduh riwayat pekerjaan per rambu dalam bentuk PDF.

### 3.6 Lainnya

**3.6.1 Manajemen Petugas.** Bagian ini menjelaskan cara menambah, mengubah, mengaktifkan, atau menonaktifkan akun petugas dan admin.

**3.6.2 Log Error Sistem.** Bagian ini menjelaskan cara melihat catatan error teknis yang tertangkap secara otomatis oleh sistem, berguna untuk keperluan pemeliharaan teknis.

---

## BAB IV: PANDUAN PENGGUNAAN UNTUK PETUGAS LAPANGAN

Bab ini disusun mengikuti urutan grup menu sidebar Petugas Lapangan, yang sengaja disusun lebih ringkas dibandingkan Bab III karena memang jumlah menu yang tersedia bagi peran ini lebih sedikit.

### 4.1 Pengerjaan

**4.1.1 Daftar Surat Aktif.** Bagian ini menjelaskan halaman awal petugas: melihat semua SPK aktif yang bisa diambil, bukan hanya yang sudah diikuti sebelumnya.

**4.1.2 Melihat Detail SPK dan Bergabung Tim.** Bagian ini menjelaskan cara membuka detail sebuah SPK, cara mendaftarkan diri sebagai perwakilan beserta rekan setim, dan cara menambah atau menghapus anggota di kemudian hari.

**4.1.3 Mengisi Laporan Pengerjaan.** Bagian ini menjelaskan langkah mengirim laporan hasil kerja: foto sesudah, koordinat GPS, catatan, dan daftar barang atau bahan, dengan catatan bahwa fitur ini hanya bisa dioperasikan oleh perwakilan tim.

**4.1.4 Mengajukan Kendala.** Bagian ini menjelaskan langkah melapor apabila pekerjaan tidak bisa dilanjutkan: foto dan alasan yang wajib diisi, juga khusus untuk perwakilan tim.

**4.1.5 Mengajukan Laporan Akhir.** Bagian ini menjelaskan kapan tombol ini muncul (setelah semua rambu tertangani) dan apa yang terjadi setelah diajukan, yaitu SPK masuk ke antrean validasi admin.

**4.1.6 SPK Sedang Dikerjakan.** Bagian ini menjelaskan cara melihat daftar SPK yang timnya sendiri sudah bergabung.

### 4.2 Rambu

**4.2.1 Peta Rambu.** Bagian ini menjelaskan cara melihat sebaran rambu di peta, dan cara melaporkan temuan langsung dari kartu informasi pin.

**4.2.2 Daftar Rambu.** Bagian ini menjelaskan cara melihat daftar rambu yang terdaftar, bersifat baca-saja bagi petugas.

**4.2.3 Jenis Rambu.** Bagian ini menjelaskan cara melihat referensi jenis rambu, juga bersifat baca-saja.

### 4.3 Laporan dan Riwayat

**4.3.1 Laporan Temuan Kondisi.** Bagian ini menjelaskan cara melaporkan rambu yang ditemukan rusak di lapangan, sepenuhnya independen dari SPK aktif mana pun.

**4.3.2 Riwayat Pekerjaan Saya.** Bagian ini menjelaskan cara melihat SPK yang pernah dikerjakan tim, dilengkapi filter rentang tanggal "Dari Tanggal" sampai "Sampai Tanggal", di mana apabila keduanya dikosongkan, seluruh riwayat pekerjaan akan ditampilkan.

**4.3.3 Riwayat Aktivitas.** Bagian ini menjelaskan cara melihat jejak aktivitas yang dilakukan sendiri oleh petugas yang bersangkutan.

---

## BAB V: FITUR BERSAMA (ADMIN & PETUGAS)

Bab ini mengumpulkan fitur-fitur yang bisa diakses oleh kedua peran menjadi satu bab tersendiri, dengan tujuan menghindari duplikasi penjelasan yang sama persis pada Bab III dan Bab IV.

### 5.1 Detail Rambu

Bagian ini menjelaskan cara melihat riwayat foto, kondisi, dan pekerjaan dari satu rambu tertentu.

### 5.2 Notifikasi

Bagian ini menjelaskan cara membaca notifikasi lewat ikon lonceng, arti lencana belum-dibaca, dan cara mengklik kartu notifikasi untuk langsung membuka halaman yang relevan.

### 5.3 Menghubungkan Akun Telegram

Bagian ini menjelaskan langkah-langkah menghubungkan akun ke bot Telegram supaya notifikasi yang sama juga masuk sebagai pesan chat, sekaligus cara memutuskan hubungan tersebut apabila suatu saat diinginkan.

### 5.4 Mengelola Profil

Bagian ini menjelaskan cara mengubah nama, nama panggilan, dan foto profil.

### 5.5 Mengelola Keamanan Akun

Bagian ini menjelaskan cara mengganti kata sandi dan mengaktifkan atau menonaktifkan Autentikasi Dua Faktor, dengan catatan bahwa halaman ini membutuhkan konfirmasi ulang kata sandi terlebih dahulu sebelum bisa diakses.

### 5.6 Mengunduh Surat Pengantar

Bagian ini menjelaskan cara mengunduh dokumen Surat Pengantar dalam bentuk PDF dari halaman Detail SPK, dengan catatan bahwa fitur ini tersedia bagi Admin maupun anggota tim SPK yang bersangkutan.

---

## BAB VI: PENANGANAN MASALAH (TROUBLESHOOTING)

Bab ini secara khusus dibangun berdasarkan masalah-masalah nyata yang pernah ditemukan dan diperbaiki selama proses pengembangan sistem, bukan skenario hipotetis yang belum tentu relevan.

### 6.1 Lupa Kata Sandi

Bagian ini menjelaskan solusinya lewat wizard reset yang tersedia di halaman login.

### 6.2 Akun Tidak Bisa Login atau Dinonaktifkan

Bagian ini menjelaskan arti dari pesan-pesan kesalahan umum saat login, dan langkah-langkah menghubungi Admin apabila memang diperlukan.

### 6.3 Tombol "Ambil Lokasi Sekarang" Tidak Berfungsi

Bagian ini menjelaskan cara mengaktifkan izin lokasi pada pengaturan peramban apabila sebelumnya izin tersebut ditolak.

### 6.4 Belum Bisa Mengunduh Surat Pengantar

Bagian ini menjelaskan bahwa unduhan hanya aktif bagi anggota tim yang sudah bergabung ke SPK yang bersangkutan.

### 6.5 Notifikasi Telegram Tidak Masuk

Bagian ini menjelaskan langkah memastikan akun sudah terhubung dengan benar (menekan Start pada bot) dan cara menghubungkan ulang apabila diperlukan.

### 6.6 Kontak Bantuan

Bagian ini menjelaskan ke mana pengguna sebaiknya menghubungi apabila kendala yang mereka alami belum juga teratasi, misalnya kepada Admin sistem atau tim IT Dinas Perhubungan.

---

## BAB VII: LAMPIRAN

Bab penutup ini menyediakan referensi tambahan yang berguna untuk dirujuk sewaktu-waktu, terpisah dari alur pembahasan utama pada bab-bab sebelumnya.

### 7.1 Glosarium Lengkap

Bagian ini menyediakan daftar istilah lengkap: SPK, Rambu Pasang, Perwakilan, Kendala, Laporan Akhir, Urgensi, Temuan Kondisi, dan istilah-istilah lain yang relevan.

### 7.2 Daftar Kode Status

Bagian ini menyediakan tabel penjelasan tentang status rambu (Belum, Urgent, Tertunda, Menunggu Validasi, Revisi, Selesai, Batal), urgensi (Rendah, Sedang, Tinggi), dan arti dari setiap warna pin di peta.

### 7.3 Pertanyaan yang Sering Diajukan (FAQ)

Bagian ini mengumpulkan pertanyaan-pertanyaan umum dari kedua peran, misalnya "Bisakah satu rambu punya dua laporan sekaligus?" atau "Kenapa saya tidak bisa menghapus anggota tim di SPK yang sudah selesai?"

### 7.4 Kontak dan Dukungan Teknis

Bagian ini menyediakan informasi kontak tim IT atau Admin Dinas Perhubungan untuk eskalasi masalah teknis yang lebih serius.

---

## Catatan Penyusunan

Urutan Bab III (Admin) dan Bab IV (Petugas) sengaja mengikuti persis urutan grup menu sidebar milik masing-masing peran, supaya pembaca bisa membuka buku panduan sambil mengklik menu yang sama urutannya secara langsung di dalam sistem, membuat proses belajar terasa lebih alami dan tidak membingungkan.

Fitur yang muncul di kedua peran, seperti Peta, Detail Rambu, Notifikasi, Telegram, Profil, dan Keamanan, sengaja dikumpulkan menjadi satu di Bab V, bukan diulang secara terpisah di Bab III dan Bab IV, supaya isi buku panduan tidak duplikat dan tetap ringkas untuk dibaca.

Bab VI (Troubleshooting) secara khusus berisi masalah-masalah yang benar-benar pernah ditemukan dan diperbaiki selama proses pengembangan sistem berlangsung, sebagaimana bisa ditelusuri lewat riwayat commit yang relevan, bukan sekadar skenario hipotetis yang dikarang-karang tanpa dasar pengalaman nyata.
