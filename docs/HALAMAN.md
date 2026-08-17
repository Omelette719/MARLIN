# Penjelasan Halaman Sistem MARLIN

## Pengantar

Apabila [FITUR.md](FITUR.md) menjelaskan *apa* yang bisa dilakukan pengguna di dalam sistem, dokumen ini menjelaskan *di mana* dan *dengan hak apa* setiap fitur tersebut bisa diakses. Setiap halaman yang ada di dalam sistem dipetakan di sini secara lengkap: alamat URL-nya, siapa saja yang diizinkan membukanya, dan penjelasan singkat tentang fungsinya. Dokumen ini sangat berguna baik bagi pengembang yang ingin menelusuri kode dari sisi rute (routing), maupun bagi penguji yang ingin memastikan setiap halaman benar-benar dijaga aksesnya sesuai peran yang seharusnya.

Sebagai aturan umum, seluruh halaman yang dijelaskan di bawah ini berada di bawah lindungan middleware `auth`, artinya pengguna wajib login terlebih dahulu sebelum bisa mengaksesnya, kecuali disebutkan secara eksplisit sebaliknya. Halaman yang diberi label **Admin** dijaga lebih lanjut oleh middleware `role:admin`; halaman berlabel **Petugas** dijaga oleh middleware `role:user`; sementara halaman berlabel **Bersama** bisa diakses oleh siapa saja yang sudah login, apa pun peran mereka.

---

## Autentikasi

Sebelum seorang pengguna bisa masuk ke dalam sistem dan menikmati fitur-fitur yang dijelaskan pada dokumen lain, mereka harus melewati serangkaian halaman autentikasi berikut ini terlebih dahulu.

| Halaman | Rute | Akses | Keterangan |
|---|---|---|---|
| Login | `GET /login` | Publik (tamu) | Proses login menggunakan NIP dan kata sandi, bukan alamat surel |
| Verifikasi 2FA | `GET /two-factor-challenge` | Tamu, dalam proses login yang sedang berlangsung | Halaman ini hanya muncul apabila akun yang bersangkutan sudah mengaktifkan Autentikasi Dua Faktor |
| Konfirmasi Kata Sandi | `GET /user/confirm-password` | Sudah login (auth) | Berfungsi sebagai gerbang sebelum pengguna bisa masuk ke halaman-halaman yang sensitif, khususnya Settings menuju Security |
| Reset Kata Sandi (modal) | (berupa modal di halaman login, bukan rute tersendiri) | Publik | Sebuah wizard dua langkah: pertama memverifikasi NIP dan kata sandi lama, lalu menetapkan kata sandi baru. Dibatasi maksimal lima percobaan per menit, dihitung per kombinasi NIP dan alamat IP |

## Dashboard

Setiap peran memiliki dashboard tersendiri yang menyambut mereka begitu berhasil login, meski keduanya menyajikan informasi yang sangat berbeda sesuai kebutuhan masing-masing peran.

| Halaman | Rute | Akses | Keterangan |
|---|---|---|---|
| Dashboard Admin | `GET /admin/dashboard` | Admin | Menyajikan ringkasan jumlah SPK aktif, rambu rusak, dan laporan yang menunggu validasi |
| Daftar Surat Aktif (Dashboard Petugas) | `GET /dashboard` | Petugas | Menampilkan **semua** SPK yang berstatus aktif, bukan hanya yang sudah diikuti, menjadikannya tempat petugas menemukan pekerjaan baru |

## Manajemen SPK (Admin)

Kelompok halaman ini menaungi seluruh siklus hidup SPK dari sisi admin, mulai dari pembuatan hingga arsip.

| Halaman | Rute | Akses | Keterangan |
|---|---|---|---|
| Daftar Surat | `GET /admin/spk` | Admin | Daftar SPK yang **berstatus Aktif saja**, dilengkapi filter jenis pekerjaan dan pencarian |
| Riwayat SPK | `GET /admin/spk-riwayat` | Admin | Arsip SPK yang berstatus Selesai atau Dibatalkan, dilengkapi filter status, filter jenis, filter rentang tanggal berdasarkan kapan SPK terakhir diperbarui, dan pencarian |
| Buat Surat | `GET /admin/spk/create` | Admin | Formulir untuk membuat SPK baru, dengan setiap baris rambu memilih jenis pekerjaannya sendiri |
| Detail Surat | `GET /admin/spk/{spk}` | Admin | Menampilkan detail lengkap SPK, tim yang bertugas, dan daftar rambu yang tercakup di dalamnya. Tombol Edit dan Batalkan hanya muncul selama status SPK masih Aktif |
| Edit Surat | `GET /admin/spk/{spk}/edit` | Admin | Hanya bisa diakses apabila SPK masih berstatus Aktif (akan menampilkan kode kesalahan 403 apabila SPK sudah Selesai atau Dibatalkan). Rambu yang statusnya sudah beranjak dari Belum/Urgent/Revisi hanya ditampilkan sebagai ringkasan baca-saja, tidak lagi bisa diedit |
| Validasi Laporan | `GET /admin/validasi` | Admin | Daftar SPK yang sudah mengajukan Laporan Akhir dan siap untuk divalidasi |
| Detail Validasi | `GET /admin/validasi/{spk}` | Admin | Tempat admin menerima atau menolak laporan pengerjaan untuk setiap rambu |
| Temuan Lapangan | `GET /admin/temuan` | Admin | Antrean laporan kondisi rusak yang belum dibuatkan SPK |
| Laporan Bulanan | `GET /admin/laporan` | Admin | Rekap dengan filter rentang tanggal, jenis rambu, dan status yang bebas ditentukan, bisa diekspor ke PDF lewat `GET /admin/laporan/export` |
| Laporan Rambu | `GET /admin/laporan/rambu` | Admin | Riwayat pekerjaan per rambu, dengan filter yang sama seperti Laporan Bulanan, diekspor lewat `GET /admin/laporan/rambu/export` |

## Manajemen Master Data dan Pengguna (Admin)

| Halaman | Rute | Akses | Keterangan |
|---|---|---|---|
| Manajemen Petugas | `GET /admin/users` | Admin | Daftar seluruh akun pengguna yang terdaftar |
| Tambah Petugas | `GET /admin/users/create` | Admin | Formulir untuk membuat akun baru |
| Edit Petugas | `GET /admin/users/{user}/edit` | Admin | Mengubah data akun, termasuk mengaktifkan atau menonaktifkannya |
| Jenis Rambu (versi admin) | `GET /admin/jenis-rambu` | Admin | Sama persis dengan versi bersama di bawah, hanya berbeda alamat URL-nya (`/admin/jenis-rambu` dibandingkan `/jenis-rambu`). Fungsi tambah/ubah/hapus hanya berfungsi untuk admin, walau petugas secara teknis bisa membuka alamat URL yang sama |
| Daftar Rambu (versi admin) | `GET /admin/rambu` | Admin | Sama persis dengan versi bersama (`/rambu`), sebagai alamat URL alternatif |
| Log Error Sistem | `GET /admin/system-error-log` | Admin | Menampilkan exception tak terduga (level 500 ke atas) yang tertangkap secara otomatis |

## Petugas Lapangan

| Halaman | Rute | Akses | Keterangan |
|---|---|---|---|
| SPK Sedang Dikerjakan | `GET /spk-dikerjakan` | Petugas | Sebuah subset dari Dashboard, hanya menampilkan SPK yang timnya sudah bergabung |
| Detail SPK (versi petugas) | `GET /spk/{spk}` | Petugas | **Sengaja dibuka untuk SPK aktif mana pun**, bukan hanya yang sudah diikuti, karena dibutuhkan untuk alur "melihat dulu sebelum memutuskan bergabung ke tim". Penjelasan lengkap tentang keputusan desain ini tersedia di [KEAMANAN.md](KEAMANAN.md) |
| Laporan Kendala | `GET /kendala` | Petugas | Formulir untuk melaporkan kendala pemasangan atau perbaikan. Bisa diedit ulang atau ditukar menjadi Laporan Pengerjaan, selama SPK yang menaunginya belum mengajukan Laporan Akhir |
| Laporan Pengerjaan | `GET /laporan` | Petugas | Formulir laporan hasil kerja (foto, GPS, barang/bahan). Bisa diedit ulang atau ditukar menjadi Laporan Kendala, selama SPK yang menaunginya belum mengajukan Laporan Akhir |
| Riwayat Pekerjaan Saya | `GET /riwayat-spk` | Petugas | SPK yang pernah dikerjakan tim, dilengkapi filter rentang tanggal "Dari Tanggal" sampai "Sampai Tanggal" berdasarkan kapan petugas bergabung; apabila keduanya dikosongkan, seluruh riwayat ditampilkan |
| Lapor Temuan Kondisi | `GET /temuan` | Petugas | Melaporkan rambu rusak, sepenuhnya independen dari SPK aktif mana pun |

## Bersama (Admin & Petugas)

| Halaman | Rute | Akses | Keterangan |
|---|---|---|---|
| Peta Rambu | `GET /peta` | Siapa saja yang sudah login | Peta interaktif seluruh rambu; parameter `?focus={rambu_id}` bisa dipakai untuk otomatis memperbesar tampilan dan membuka kartu rambu tertentu |
| Data Peta (JSON) | `GET /peta/data` | Siapa saja yang sudah login | Sebuah endpoint JSON yang dikonsumsi oleh JavaScript pada halaman peta, bukan sebuah halaman yang dikunjungi langsung, dipanggil lewat fetch |
| Ekspor Peta (PDF) | `POST /peta/export` | Siapa saja yang sudah login | Dipanggil oleh tombol Unduh PDF pada widget peta di Dashboard Admin; menerima gambar peta hasil tangkapan (capture) dari peramban beserta filter yang sedang aktif, lalu mengembalikan berkas PDF laporan sebaran rambu |
| Detail Rambu | `GET /rambu/{rambu}` | Siapa saja yang sudah login | Riwayat foto dan pekerjaan rambu; tombol Ke Halaman Validasi khusus muncul bagi admin apabila relevan |
| Jenis Rambu | `GET /jenis-rambu` | Siapa saja yang sudah login | Referensi kategori rambu |
| Daftar Rambu | `GET /rambu` | Siapa saja yang sudah login | Daftar seluruh rambu yang terpasang |
| Surat Pengantar (PDF) | `GET /spk/{spk}/surat-pengantar` | Admin, atau petugas yang menjadi anggota tim SPK tersebut | **Satu-satunya halaman terkait-SPK yang membatasi akses berdasarkan keanggotaan tim**, bukan hanya berdasarkan peran semata. Lihat penjelasan lengkapnya di [KEAMANAN.md](KEAMANAN.md) |
| Notifikasi | `GET /notifikasi` | Siapa saja yang sudah login | Daftar notifikasi in-app milik sendiri, ditampilkan sebagai kartu-kartu terpisah; kartu yang memiliki tujuan halaman bisa diklik langsung (tanpa perlu tombol Lihat yang terpisah) |
| Riwayat Aktivitas | `GET /audit-log` | Siapa saja yang sudah login | Admin melihat seluruh aktivitas sistem, dilengkapi filter aksi, filter pengguna, dan filter rentang tanggal; petugas hanya melihat aksinya sendiri, dengan filter aksi dan rentang tanggal saja |
| Settings menuju Profil | `GET /settings/profile` | Siapa saja yang sudah login | Mengubah nama, foto, dan data diri |
| Settings menuju Keamanan | `GET /settings/security` | Siapa saja yang sudah login | Mengganti kata sandi dan mengelola Autentikasi Dua Faktor. **Memerlukan konfirmasi ulang kata sandi** sebelum bisa diakses |
| Settings menuju Telegram | `GET /settings/telegram` | Siapa saja yang sudah login | Menghubungkan atau memutuskan akun Telegram untuk menerima notifikasi lewat chat, di luar halaman Notifikasi in-app |

---

## Catatan Penting yang Perlu Diperhatikan

Ada beberapa hal teknis penting yang layak diperhatikan lebih lanjut tentang bagaimana routing di dalam sistem ini disusun.

Alamat `/rambu` dan `/jenis-rambu` sengaja **didaftarkan dua kali**: sekali di luar prefix `admin/` (bisa diakses siapa saja yang sudah login), dan sekali lagi di dalam grup rute `admin/` (menghasilkan alamat `/admin/rambu` dan `/admin/jenis-rambu`, khusus admin). Keduanya sebenarnya memakai komponen Livewire yang persis sama; ini bukan bentuk duplikasi yang berbahaya, melainkan sekadar dua alamat URL yang berbeda menuju halaman yang sama. Halaman **Daftar Rambu** memang murni bersifat baca-saja (tidak ada aksi ubah atau hapus apa pun di dalam komponennya), sehingga tidak ada apa pun yang perlu dijaga di luar tampilan itu sendiri. Halaman **Jenis Rambu**, di sisi lain, memiliki aksi tambah, ubah, dan hapus, dan komponennya sendiri secara eksplisit memanggil pemeriksaan `abort_unless(Auth::user()->isAdmin(), 403)` pada setiap aksi tersebut. Artinya, walaupun seorang petugas membuka alamat `/jenis-rambu`, tombol-tombol untuk membuat, mengubah, atau menghapus data tidak akan berfungsi bagi mereka (akan menghasilkan kode kesalahan 403), bukan sekadar disembunyikan dari tampilan saja.

Sebagai penegasan ulang, **seluruh rute berada di bawah middleware `auth`**, kecuali halaman login, halaman tantangan 2FA, dan wizard reset kata sandi, yang memang harus bisa diakses sebelum seseorang berhasil login. Tidak ada satu pun halaman berisi data sungguhan yang bisa diakses tanpa login sama sekali di dalam sistem ini.
