# Keamanan Sistem MARLIN

## Pengantar

Dokumen ini merinci setiap langkah keamanan yang sudah diterapkan di dalam Sistem MARLIN, sekaligus menjelaskan batasan-batasan dan keputusan desain yang sengaja diambil, yang mungkin pada pandangan pertama terlihat seperti sebuah celah, padahal sebenarnya adalah pilihan yang sudah dipertimbangkan secara matang. Membedakan antara "celah keamanan yang nyata" dengan "keputusan desain yang disengaja" adalah tujuan utama dokumen ini, karena tanpa penjelasan yang jelas, siapa pun yang mengaudit sistem ini bisa saja salah menilai sesuatu yang sebenarnya aman sebagai sebuah kerentanan, atau sebaliknya, melewatkan sesuatu yang sebenarnya memang perlu diperkuat.

Setiap poin di dalam dokumen ini diverifikasi langsung dari kode sumber yang sesungguhnya berjalan, bukan sekadar berdasarkan asumsi atau ingatan tentang bagaimana sistem seharusnya bekerja.

---

## Daftar Isi

- [Autentikasi](#autentikasi)
- [Otorisasi dan Kontrol Akses Berbasis Peran](#otorisasi-dan-kontrol-akses-berbasis-peran)
- [Proteksi Data dan Input](#proteksi-data-dan-input)
- [Unggahan Berkas](#unggahan-berkas)
- [Jejak Audit](#jejak-audit)
- [Keputusan Desain yang Disengaja (Bukan Celah)](#keputusan-desain-yang-disengaja-bukan-celah)
- [Batasan yang Diketahui](#batasan-yang-diketahui)

---

## Autentikasi

Proses masuk ke dalam sistem menggunakan **Nomor Induk Pegawai (NIP)** sebagai identitas utama, bukan alamat surel seperti kebanyakan aplikasi pada umumnya, dikonfigurasi lewat berkas `config/fortify.php` pada baris `'username' => 'nip'`. Kredensial yang dimasukkan pengguna diverifikasi lewat sebuah callback khusus di `app/Providers/FortifyServiceProvider.php`, bukan mekanisme bawaan Fortify apa adanya. Pendekatan kustom ini memungkinkan sistem untuk sekaligus memeriksa status aktif atau tidaknya sebuah akun pada saat yang bersamaan dengan verifikasi kata sandi, sesuatu yang tidak bisa dilakukan hanya dengan mengandalkan mekanisme bawaan.

Kata sandi disimpan dengan algoritma **bcrypt**, menggunakan cost factor sebesar 12, dikonfigurasi lewat variabel lingkungan `BCRYPT_ROUNDS=12` pada berkas `.env`. Kolom `password` pada model `User` diberi cast `'hashed'`, yang berarti setiap kali kolom tersebut diisi dengan nilai apa pun, ia otomatis di-hash secara transparan. Konsekuensinya, tidak ada satu pun jalur kode di seluruh sistem yang menyimpan kata sandi dalam bentuk mentahnya, karena mekanisme hashing ini sudah tertanam pada level model itu sendiri, sehingga sulit untuk secara tidak sengaja melewatinya.

**Autentikasi Dua Faktor (2FA)** juga tersedia bagi setiap pengguna, bersifat opsional, dan wajib dikonfirmasi ulang dengan kata sandi sebelum bisa diaktifkan atau diubah, dikonfigurasi lewat pengaturan `confirmPassword: true` pada `config/fortify.php`. Fitur ini bisa dikelola oleh setiap pengguna sendiri lewat halaman Settings menuju Keamanan.

Untuk mencegah upaya menebak kata sandi secara berulang-ulang (*brute force*), sistem menerapkan pembatasan laju (*rate limiting*) pada tiga titik berikut. Percobaan login dibatasi maksimal **lima kali per menit**, dihitung dan dikunci berdasarkan kombinasi NIP dan alamat IP pengirim permintaan, diatur pada `app/Providers/FortifyServiceProvider.php`. Verifikasi kode 2FA juga dibatasi maksimal lima percobaan per menit, dihitung per sesi login yang sedang berlangsung saat itu. Dan wizard reset kata sandi mandiri, yang bisa diakses lewat modal di halaman login, juga dibatasi lima percobaan per menit dengan pola pembatasan yang sama, berdasarkan kombinasi NIP dan alamat IP.

Admin memiliki wewenang untuk **menonaktifkan akun** petugas dengan mengubah kolom `aktif` menjadi salah. Begitu sebuah akun dinonaktifkan, percobaan login berikutnya akan ditolak dengan pesan yang jelas, menyatakan bahwa "Akun ini telah dinonaktifkan..."

Perlu ditekankan sebuah catatan penting di sini: pemeriksaan status `aktif` hanya terjadi **pada saat proses login berlangsung**. Apabila sebuah akun dinonaktifkan tepat pada saat penggunanya sedang login dengan sesi yang masih aktif, sesi tersebut tidak akan otomatis terputus begitu saja. Pengguna yang bersangkutan baru benar-benar terblokir ketika mereka mencoba login ulang di kemudian hari. Perilaku ini sesuai dengan mekanisme bawaan sesi Laravel yang bersifat server-side (bukan token yang divalidasi ulang pada setiap permintaan), dan sudah wajar untuk skala aplikasi seperti ini, meski penting untuk diketahui apabila suatu saat dibutuhkan fitur "paksa keluar" secara instan.

**Pendaftaran akun mandiri sengaja dinonaktifkan.** Fitur registrasi bawaan dari Fortify memang tidak diaktifkan sama sekali di dalam sistem ini. Seluruh akun dibuat secara terpusat oleh admin lewat halaman Manajemen Petugas, tidak ada satu pun halaman "daftar akun baru" yang bisa diakses secara publik oleh siapa saja.

Untuk fitur **Telegram**, kolom `telegram_link_token` berisi sebuah token acak sepanjang 32 karakter (dihasilkan lewat `Str::random(32)`) yang hanya berlaku satu kali pakai, dan langsung dikosongkan kembali begitu berhasil digunakan atau digenerate ulang. Bot Telegram juga memvalidasi terlebih dahulu apakah sebuah `chat_id` sudah terhubung ke akun lain sebelum mengizinkan hubungan baru, mencegah satu chat Telegram dipakai untuk "mencuri" notifikasi milik akun orang lain. Token dan chat ID ini tidak pernah dipakai sebagai kredensial untuk login, mereka semata-mata hanya menentukan ke mana pesan notifikasi seharusnya dikirimkan.

## Otorisasi dan Kontrol Akses Berbasis Peran

Middleware `role:admin` dan `role:user`, diimplementasikan pada berkas `app/Http/Middleware/EnsureUserHasRole.php`, menjaga setiap grup rute sesuai dengan peran yang diperbolehkan mengaksesnya. Ketika seorang pengguna ketahuan mencoba mengakses halaman yang bukan untuk perannya, mereka dialihkan (redirect) ke dashboard sesuai dengan peran mereka sendiri, bukan ditampilkan sebuah halaman kesalahan yang eksplisit. Efeknya, sebuah percobaan mengakses halaman peran lain terasa seolah-olah "halaman itu tidak pernah ada", bukan terasa seperti "Anda ditolak masuk", sebuah nuansa yang sedikit namun tetap penting, karena mengurangi informasi yang bisa dimanfaatkan seseorang untuk memetakan struktur internal sistem hanya lewat percobaan coba-coba pada alamat URL.

Untuk aksi-aksi yang bersifat sensitif, sistem mewajibkan **konfirmasi ulang kata sandi** terlebih dahulu. Halaman Settings menuju Keamanan (mengganti kata sandi, mengelola 2FA) mewajibkan pengguna mengonfirmasi ulang kata sandi mereka lewat middleware `password.confirm`, sebelum bisa mengakses halaman tersebut, walaupun sesi login mereka masih berjalan normal.

Terkait *mass assignment*, seluruh model di dalam sistem memakai atribut PHP `#[Fillable([...])]` yang secara eksplisit menyebutkan kolom mana saja yang boleh diisi lewat method `create()` atau `update()`. Tidak ada satu pun model yang memakai `$guarded = []`, sebuah pola yang berarti "semua kolom boleh diisi", yang secara umum lebih rawan disalahgunakan lewat data input dari formulir yang telah dimanipulasi oleh pihak yang tidak bertanggung jawab.

## Proteksi Data dan Input

Beberapa lapisan proteksi standar diterapkan secara konsisten di seluruh sistem untuk mencegah kategori serangan yang paling umum ditemukan pada aplikasi web.

Untuk **CSRF (Cross-Site Request Forgery)**, seluruh formulir HTML biasa, seperti login, verifikasi 2FA, dan konfirmasi kata sandi, memakai token `@csrf`. Formulir yang dikelola oleh Livewire (memakai `wire:submit`) sudah memiliki proteksi CSRF bawaan dari Livewire itu sendiri, sehingga token terpisah tidak diperlukan lagi untuk formulir-formulir tersebut.

Untuk **XSS (Cross-Site Scripting)**, hampir seluruh output pada berkas Blade memakai sintaks `{{ }}`, yang secara otomatis melakukan escaping terhadap karakter-karakter berbahaya. Hanya **satu** tempat di seluruh sistem yang memakai output mentah lewat sintaks `{!! !!}`, yaitu kode QR untuk pengaturan 2FA, yang dihasilkan dari SVG buatan server itu sendiri, bukan berasal dari input pengguna, sehingga bukan merupakan sebuah celah XSS.

Untuk **SQL Injection**, seluruh kueri di dalam sistem memakai Eloquent, yang secara otomatis menghasilkan parameterized query. Ada beberapa penggunaan `selectRaw` dan `orderByRaw` di dalam sistem, digunakan untuk keperluan agregasi pada dashboard dan pengurutan data yang memprioritaskan kondisi rusak lebih dulu, namun seluruhnya memakai string SQL yang bersifat tetap dan statis, tidak ada satu pun input dari pengguna yang digabungkan secara langsung ke dalam kueri SQL tersebut.

Untuk **sesi pengguna**, sistem memakai driver `database`, dengan masa berlaku 120 menit, dan cookie yang bersifat `HttpOnly` serta `SameSite=Lax`, mengikuti nilai bawaan Laravel. Enkripsi cookie sesi tidak diaktifkan (`SESSION_ENCRYPT=false`), sebuah pengaturan yang standar untuk aplikasi internal yang beroperasi di dalam jaringan yang terpercaya. Namun, apabila suatu saat sistem ini diakses lewat internet publik tanpa proteksi HTTPS, pertimbangkan untuk mengaktifkan `SESSION_SECURE_COOKIE` pada server produksi.

## Unggahan Berkas

Seluruh berkas yang diunggah ke dalam sistem dibatasi ukurannya maksimal **5 MB**, dan divalidasi tipe berkasnya sesuai konteks penggunaannya masing-masing.

| Jenis Unggahan | Aturan | Digunakan pada |
|---|---|---|
| Foto (survei, sesudah, kendala, temuan) | Bertipe `image`, maksimal 5 MB | Buat/Edit SPK, Laporan Pengerjaan, Kendala, Temuan Kondisi |
| Gambar referensi jenis rambu | Bertipe `image`, maksimal 5 MB | Kelola Jenis Rambu |
| Berkas referensi SPK | Bertipe `file` (bebas jenisnya), maksimal 5 MB | Buat/Edit SPK, digunakan untuk pindaian (scan) surat, boleh berformat PDF |

Seluruh berkas tersimpan pada disk `public` (`storage/app/public`, diekspos lewat symlink `public/storage`). Perlu diperhatikan bahwa **berkas yang sudah terunggah bisa diakses oleh siapa saja yang mengetahui URL-nya, tanpa perlu login terlebih dahulu**. Ini adalah pola yang standar untuk berkas statis pada banyak aplikasi Laravel dengan skala serupa, namun berarti URL foto atau dokumen sebaiknya tidak bocor kepada pihak yang tidak seharusnya melihatnya. URL berkas itu sendiri berisi nama berkas yang dihasilkan secara acak lewat `Storage::store()`, sehingga tidak mudah ditebak begitu saja, meski demikian ini tetap bukan proteksi akses yang sesungguhnya dalam artian teknis yang ketat.

## Jejak Audit

Tabel `audit_log` mencatat aksi-aksi bisnis yang dianggap penting, seperti pembuatan, pengeditan, dan pembatalan SPK, pengiriman, penerimaan, atau penolakan laporan, pengajuan kendala, dan sebagainya. Sifat tabel ini adalah **append-only**, artinya tidak ada kolom `updated_at` sama sekali, sehingga catatan yang sudah tersimpan tidak bisa diubah lagi lewat jalur normal aplikasi setelah ia tercatat.

Admin bisa melihat **seluruh** riwayat aktivitas yang ada di dalam sistem, tanpa terkecuali. Petugas, di sisi lain, hanya bisa melihat aktivitas yang **mereka lakukan sendiri** (dipetakan berdasarkan kolom `user_id` milik mereka), bukan aktivitas admin ataupun rekan setim mereka yang lain, sekalipun mereka berada dalam SPK yang persis sama.

## Keputusan Desain yang Disengaja (Bukan Celah)

Bagian ini secara khusus membahas beberapa hal yang mungkin terlihat seperti celah keamanan pada pandangan pertama, namun sebenarnya adalah keputusan desain yang sudah dipertimbangkan dengan matang.

### Petugas Bisa Membuka Detail SPK Apa Pun (Selama Masih Aktif), Bukan Hanya yang Diikuti

Halaman `/spk/{spk}` versi petugas sengaja **tidak** dibatasi hanya untuk anggota tim yang sudah bergabung ke SPK tersebut. Alasannya berkaitan langsung dengan bagaimana halaman "Daftar Surat Aktif" (dashboard petugas) dirancang untuk menampilkan **semua** SPK yang berstatus aktif, supaya petugas bisa menemukan dan memilih pekerjaan baru untuk mereka ambil, dan tombol "Lihat Detail" pada halaman tersebut mengarah ke halaman yang sama ini. Apabila halaman ini dibatasi hanya untuk anggota tim yang sudah tergabung, seorang petugas tidak akan pernah bisa melihat detail sebuah SPK baru untuk memutuskan apakah mereka ingin bergabung atau tidak, sehingga alur inti dari fitur "gabung tim" itu sendiri akan menjadi rusak sama sekali.

Meski demikian, aksi-aksi yang benar-benar mengubah data, seperti `daftarkanTim`, `tambahAnggota`, `hapusAnggota`, dan `ajukanLaporanAkhir`, tetap memiliki pengecekan keanggotaan tersendiri di dalam kode masing-masing. Dengan kata lain, yang dibuka secara luas hanyalah kemampuan untuk **melihat**, bukan kemampuan untuk **mengubah**. Setiap aksi tersebut juga meminta konfirmasi eksplisit terlebih dahulu di antarmuka sebelum benar-benar dieksekusi. Khusus untuk `daftarkanTim`, aksi ini sama sekali tidak bisa dibatalkan lewat sistem, karena memang tidak ada fitur "keluar dari tim" yang tersedia; sementara `hapusAnggota` hanya bisa menghapus baris anggota yang bukan perwakilan, baris keanggotaan perwakilan (`is_perwakilan = true`) tidak pernah bisa dihapus lewat aksi ini.

Sebagai perbandingan yang sengaja dibuat berbeda, tombol **unduh PDF Surat Pengantar** (`/spk/{spk}/surat-pengantar`) justru DIBATASI hanya untuk admin atau petugas yang sudah terdaftar sebagai anggota tim SPK tersebut, sebagaimana bisa dilihat pada `app/Http/Controllers/SuratPengantarController.php`. Ini karena Surat Pengantar adalah dokumen kerja resmi yang hanya relevan bagi mereka yang benar-benar sedang mengerjakannya, sebuah konteks yang jauh berbeda dengan "melihat-lihat sekilas untuk memutuskan apakah ingin bergabung".

### ID Numerik Berurutan pada URL, Bukan UUID

Seluruh rute yang terikat pada sebuah model (`/rambu/{rambu}`, `/admin/spk/{spk}`, `/spk/{spk}`, `/admin/users/{user}`, dan seterusnya) memakai primary key integer biasa, yang berarti nilainya bisa ditebak atau diurutkan dengan mudah, misalnya cukup mengganti angka `5` menjadi `6` pada URL untuk berpindah ke record berikutnya. Ini adalah keputusan yang **sengaja tidak dianggap sebagai masalah** di dalam sistem ini, karena proteksi yang sesungguhnya diberikan bukan berasal dari sulitnya menebak ID, melainkan dari pengecekan otorisasi yang dilakukan di sisi server untuk setiap aksi yang benar-benar bersifat sensitif.

Sebagai contoh konkret, notifikasi (lewat aksi `tandaiDibaca` dan `bukaNotifikasi`) selalu disaring (`scoped`) dengan kondisi `where('user_id', Auth::id())`, sehingga ID notifikasi milik orang lain tidak bisa dibuka atau ditandai terbaca sekali pun ID-nya berhasil ditebak dengan tepat. Aksi `hapusAnggota` juga disaring dengan kondisi `where('by_spk_id', $this->spk->id)`, sehingga seorang perwakilan tim SPK A tidak bisa menghapus anggota tim SPK B lewat ID `dikerjakan_oleh` yang kebetulan berhasil ditebak. Halaman-halaman admin (di bawah prefix `/admin/*`) memang sengaja dirancang supaya admin bisa melihat dan mengelola SEMUA record yang ada, sehingga kemampuan "mengakses record apa pun lewat ID" pada halaman-halaman tersebut memang merupakan perilaku yang benar sesuai desain, bukan sebuah celah. Data yang memang sengaja dibuka luas bagi seluruh peran yang sudah login, seperti data rambu dan SPK aktif bagi petugas, juga sudah dijelaskan alasannya secara terperinci pada bagian sebelumnya.

Apabila suatu saat ada halaman baru yang menampilkan data milik satu pengguna tertentu secara spesifik, pola yang harus diikuti sama seperti pada fitur Notifikasi di atas: kueri harus disaring berdasarkan `Auth::id()` atau berdasarkan status keanggotaan yang relevan, bukan mengandalkan asumsi bahwa ID-nya "sulit ditebak" sebagai satu-satunya lapisan proteksi.

## Batasan yang Diketahui

Hal-hal berikut ini **bukan** merupakan kesalahan, melainkan sebuah trade-off yang masuk akal mengingat skala dan konteks penggunaan aplikasi ini, yaitu sebagai aplikasi internal yang dipakai oleh staf Dinas Perhubungan dan tim lapangan yang jumlahnya terbatas.

Pertama, **tidak ada mekanisme "paksa keluar" (force logout) secara instan** begitu sebuah akun dinonaktifkan. Sesi yang sedang berjalan tetap berlanjut sampai pengguna keluar secara manual atau sesinya kedaluwarsa dengan sendirinya setelah 120 menit.

Kedua, **berkas yang diunggah tidak divalidasi ulang setiap kali diakses**. Begitu sebuah berkas berhasil terunggah, URL-nya bisa diakses tanpa perlu login, meskipun nama berkasnya bersifat acak dan bukan sesuatu yang mudah ditebak.

Ketiga, **wizard reset kata sandi mandiri tidak memeriksa status `aktif`** sebuah akun. Akun yang sudah dinonaktifkan tetap bisa mengganti kata sandinya sendiri lewat modal reset yang tersedia di halaman login, meskipun setelahnya mereka tetap tidak akan bisa login, karena pemeriksaan status `aktif` memang berada pada jalur login, bukan pada jalur penggantian kata sandi.

Keempat, konfigurasi `SESSION_ENCRYPT=false` dan ketiadaan `SESSION_SECURE_COOKIE` secara eksplisit pada berkas `.env.example`. Apabila sistem ini suatu saat dipasang ke server produksi yang diakses lewat internet publik, aktifkan HTTPS terlebih dahulu, dan pertimbangkan untuk mengatur `SESSION_SECURE_COOKIE=true`.

Kelima, **bot Telegram berjalan lewat mekanisme long-polling** (`php artisan telegram:poll`), bukan lewat webhook, karena sistem ini belum memiliki domain publik dengan HTTPS. Proses ini, beserta `php artisan queue:work` yang menangani pengiriman pesannya, harus dijalankan secara manual dan terpisah dari server web utama. Apabila proses ini tidak berjalan, fitur notifikasi Telegram hanya akan diam-diam gagal mengirim pesan apa pun, tanpa menampilkan pesan kesalahan apa pun yang terlihat oleh pengguna.

Keenam, `TelegramService` hanya menyertakan tombol "Buka Halaman" pada pesan yang dikirimkannya apabila alamat host pada URL notifikasi bersifat publik atau bisa di-resolve (bukan `localhost`, `127.0.0.1`, `*.test`, atau `*.local`). Ini karena Telegram API akan menolak **seluruh pesan** (bukan hanya tombolnya saja) apabila URL tombol inline yang disertakan tidak valid, sehingga pada lingkungan pengembangan lokal (`APP_URL` yang mengarah ke domain lokal), pesan tetap terkirim namun tanpa tombol tersebut. Begitu sistem dipasang ke domain publik, tombolnya akan otomatis kembali muncul tanpa perlu ada perubahan kode apa pun.

Ketujuh, berkas `.env.example` mengatur `APP_DEBUG=true`, yang memang cocok untuk lingkungan pengembangan. Pengaturan ini **wajib** diubah menjadi `APP_DEBUG=false` pada berkas `.env` di lingkungan produksi sebelum dipasang untuk diakses publik. Apabila tidak diubah, setiap kesalahan yang tak tertangani akan menampilkan stack trace lengkap, mencakup path server dan kueri SQL, langsung kepada pengguna, alih-alih menampilkan halaman kesalahan generik yang lebih aman.
