# Skema Basis Data Sistem MARLIN

## Mengapa Dokumen Ini Penting

Basis data adalah fondasi paling dasar dari sistem mana pun, dan MARLIN tidak terkecuali. Setiap fitur yang dijelaskan pada [FITUR.md](FITUR.md), setiap aturan bisnis yang diuraikan pada [ALUR-BISNIS.md](ALUR-BISNIS.md), pada akhirnya bermuara pada bagaimana data disimpan, dihubungkan, dan dijaga integritasnya di dalam basis data. Dokumen ini menjadi referensi lengkap untuk memahami setiap tabel domain yang ada di dalam sistem: kolom apa saja yang dimilikinya, tipe data yang dipakai, nilai bawaan yang diterapkan, dan yang tidak kalah pentingnya, aturan penghapusan data (foreign key) yang menentukan apa yang terjadi ketika sebuah baris data yang dirujuk oleh baris lain hendak dihapus.

Untuk ringkasan yang lebih singkat, silakan lihat bagian [Rancangan Struktur Basis Data](../README.md#rancangan-struktur-basis-data) pada README.md di direktori akar proyek. Untuk memahami seluruh nilai enum yang dipakai di dalam sistem beserta artinya masing-masing, gulir ke bagian [Enum](#enum) pada dokumen ini.

Lingkungan pengembangan sistem ini menggunakan SQLite sebagai mesin basis datanya (`database/database.sqlite`). Setiap tabel domain memiliki satu berkas migrasi tersendiri di dalam direktori `database/migrations/`, dan urutan penomoran pada nama berkas-berkas tersebut secara sengaja mengikuti urutan dependensi foreign key, artinya tabel yang dirujuk oleh tabel lain selalu dibuat lebih dahulu sebelum tabel yang merujuknya.

---

## Tabel-Tabel Bawaan dari Laravel

Sebelum masuk ke tabel-tabel domain yang menjadi inti dari sistem ini, perlu disebutkan bahwa ada beberapa tabel yang sudah tersedia secara bawaan dari kerangka kerja Laravel itu sendiri. Tabel `users` termasuk di dalamnya, namun ia sudah dimodifikasi cukup signifikan dari bentuk bawaan starter kit-nya, sehingga penjelasannya akan dibahas secara terperinci di bagian tersendiri di bawah. Selain itu, tabel-tabel seperti `sessions`, `cache`, `cache_locks`, `jobs`, `job_batches`, dan `failed_jobs` adalah tabel bawaan Laravel standar yang tidak mengalami modifikasi apa pun, dan berfungsi sesuai dengan peruntukannya masing-masing dalam kerangka kerja: menyimpan sesi login, cache aplikasi, dan antrean pekerjaan latar belakang (queue jobs).

## Tabel `users`

Tabel ini menyimpan seluruh data akun pengguna, baik admin maupun petugas lapangan, dalam satu tabel yang sama, dibedakan lewat kolom `role`.

| Kolom | Tipe | Keterangan |
|---|---|---|
| `name` | string | Nama lengkap pengguna |
| `nama_panggilan` | string, nullable | Nama panggilan yang ditampilkan di bagian header aplikasi, misalnya "Halo, Budi" |
| `nip` | string, unique | Nomor Induk Pegawai, dipakai sebagai identitas untuk login, menggantikan alamat surel |
| `username` | string, unique, nullable | Belum dipakai untuk fitur apa pun saat ini, disiapkan sebagai antisipasi kebutuhan di masa mendatang |
| `role` | string, default `user` | Bernilai `admin` atau `user` (petugas), mengikuti enum `Role` |
| `tanggal_lahir` | date, nullable | |
| `jenis_kelamin` | string(1), nullable | Bernilai `L` atau `P` |
| `bidang` | string, nullable | Bidang atau divisi tempat pengguna bertugas di lingkungan Dishub |
| `jabatan` | string, nullable | |
| `no_telepon` | string(20), nullable | |
| `aktif` | boolean, default `true` | Memungkinkan sebuah akun dinonaktifkan tanpa perlu menghapusnya secara permanen |
| `telegram_chat_id` | string, unique, nullable | Terisi secara otomatis begitu akun berhasil dihubungkan dengan bot Telegram |
| `telegram_link_token` | string, unique, nullable | Token sekali pakai untuk proses menghubungkan akun ke Telegram, dikosongkan kembali begitu berhasil dipakai atau digenerate ulang |
| `password` | string | Otomatis di-hash lewat cast `hashed` pada model, sehingga tidak ada satu pun jalur kode yang menyimpan kata sandi mentah |
| `two_factor_secret`, `two_factor_recovery_codes`, `two_factor_confirmed_at` | (berbagai tipe) | Dikelola sepenuhnya oleh Laravel Fortify untuk keperluan Autentikasi Dua Faktor |

## Tabel `jenis_rambu`

Tabel ini menjadi data master untuk kategori-kategori rambu lalu lintas yang dikenal sistem. Setiap kategori didefinisikan sekali di sini, kemudian dirujuk berulang kali oleh setiap rambu fisik yang termasuk ke dalam kategori tersebut.

| Kolom | Tipe | Keterangan |
|---|---|---|
| `nama_jenis` | string | |
| `spesifikasi_standar` | text, nullable | |
| `gambar_referensi` | string, nullable | Path berkas gambar yang tersimpan pada disk `public` |
| `bentuk_ikon` | string, default `bulat` | Bernilai `bulat` atau `kotak`, menentukan bentuk ikon pin yang ditampilkan di peta untuk rambu-rambu dari jenis ini |

Relasi yang dimiliki tabel ini adalah `hasMany` menuju tabel `rambu`, artinya satu jenis rambu bisa dimiliki oleh banyak rambu fisik sekaligus.

## Tabel `rambu`

Setiap baris di dalam tabel ini merepresentasikan satu rambu fisik yang benar-benar ada, atau setidaknya direncanakan untuk ada, di dunia nyata.

| Kolom | Tipe | Keterangan |
|---|---|---|
| `jenis_rambu_id` | foreign key ke `jenis_rambu`, **restrict**, terindeks | |
| `wilayah` | string, terindeks | Teks alamat gabungan, disusun secara otomatis dari `jalan`/`rt`/`kelurahan` lewat trait `ComposesWilayah` apabila memang belum diisi secara manual |
| `jalan` | string, nullable | |
| `rt` | string, nullable | |
| `kelurahan` | string, nullable | |
| `lokasi` | string | Lokasi spesifik, misalnya "perempatan 1" |
| `koordinat` | string | Disimpan dalam format teks `"lat,lng"` |
| `kondisi_terkini` | string, default `baik` | Bernilai `baik` atau `rusak`, mengikuti enum `KondisiRambu` |
| `sudah_terpasang` | boolean, default `false` | |

Relasi yang dimiliki tabel ini adalah `belongsTo` menuju `jenis_rambu`, dan `hasMany` menuju `rambu_pasang` serta `laporan_kondisi`.

Ada satu prinsip yang perlu ditegaskan dengan jelas tentang tabel ini: `rambu` adalah representasi aset fisik yang **persisten**. Sebuah baris pada tabel ini akan tetap ada selamanya, terlepas dari berapa banyak SPK yang pernah menyentuhnya, apakah SPK-SPK tersebut sukses diselesaikan, direvisi berkali-kali, atau bahkan dibatalkan. Konsep ini penting untuk dibedakan dengan tegas dari tabel `rambu_pasang` yang akan dijelaskan berikutnya, yang justru merepresentasikan sebuah *tugas* atau *pekerjaan* terhadap sebuah rambu, bukan rambunya itu sendiri.

## Tabel `spk`

Tabel ini merupakan jantung dari seluruh sistem, merepresentasikan sebuah Surat Perintah Kerja.

| Kolom | Tipe | Keterangan |
|---|---|---|
| `nomor_surat` | string, unique | Mengikuti format `SR-{tahun}/BJM/{urutan 4 digit}` |
| `dibuat_oleh` | foreign key ke `users`, **restrict** | Selalu diisi oleh admin yang membuat SPK tersebut |
| `wilayah` | string | Sama seperti pada tabel `rambu`, disusun secara komposit otomatis |
| `jalan`, `rt`, `kelurahan` | string, nullable | |
| `deadline` | date | |
| `deadline_asli` | date, nullable | Menyimpan tenggat waktu asli sebelum digeser secara otomatis oleh `App\Support\PenyesuaianDeadlineSpk` saat ada SPK prioritas lain yang dibuat |
| `prioritas` | boolean, default `false` | Apabila bernilai `true`, urgensi akan otomatis menjadi `tinggi` |
| `urgensi` | string | Bernilai `rendah`, `sedang`, atau `tinggi`, mengikuti enum `Urgensi`, dihitung otomatis dari kombinasi tenggat waktu dan status prioritas |
| `status` | string, default `aktif`, terindeks | Bernilai `aktif`, `selesai`, atau `dibatalkan`, mengikuti enum `StatusSpk` |
| `asal_permintaan` | string | Lihat penjelasan lengkap pada enum `AsalPermintaan` |
| `keterangan_asal` | string, nullable | Misalnya menyimpan nama pelapor atau instansi yang mengajukan permintaan |
| `perihal` | string, nullable | Apabila dikosongkan, akan dibuat secara otomatis saat dokumen PDF dirender |
| `tanggal_survei` | date, nullable | |
| `petugas_survei` | string, nullable | Nama petugas yang melakukan survei, dicatat secara manual sebagai teks bebas. Wajib diisi apabila `tanggal_survei` diisi |
| `file_referensi` | string, nullable | Path berkas hasil pindaian (scan) surat permohonan asli |
| `catatan_pekerja_tambahan` | string, nullable | |
| `laporan_akhir_diajukan_at` | timestamp, nullable | Berfungsi sebagai gerbang untuk masuk ke antrean validasi, penjelasan lengkap tersedia di [ALUR-BISNIS.md](ALUR-BISNIS.md) |
| `selesai_pada` | timestamp, nullable | Diisi sekali saja pada saat kolom `status` berubah menjadi `selesai`, dipakai untuk menghitung Durasi Pengerjaan dan Selisih dari Deadline |

Relasi yang dimiliki tabel ini adalah `hasMany` menuju `rambu_pasang`, `dikerjakan_oleh`, `contact_person`, dan `audit_log`, ditambah relasi `belongsToMany` menuju `users` lewat tabel penghubung `dikerjakan_oleh`.

Dua hal penting perlu ditekankan tentang tabel ini. Pertama, **SPK tidak pernah dihapus secara permanen**. Ketika sebuah pekerjaan dibatalkan, satu-satunya yang berubah adalah kolom `status`-nya menjadi `dibatalkan`, sama sekali bukan perintah `DELETE` terhadap barisnya. Kedua, dan ini merupakan keputusan desain yang cukup mendasar, **tidak ada kolom "jenis pekerjaan" di level SPK ini sama sekali**. Setiap baris `rambu_pasang` yang tercakup di dalam SPK memiliki kolom `jenis_pekerjaan`-nya sendiri-sendiri, sehingga satu SPK bisa mencampur baris berjenis Pemasangan Baru dan baris berjenis Perbaikan sekaligus dalam satu dokumen yang sama. Untuk kebutuhan tampilan yang membutuhkan satu nilai ringkas per SPK, method `Spk::jenisRingkasan()` akan mengembalikan jenis yang sama apabila seluruh baris di dalamnya memang sejenis, atau mengembalikan `null` apabila campuran, yang biasanya ditampilkan sebagai lencana "Pemasangan & Perbaikan" pada berbagai permukaan tampilan yang membutuhkannya.

## Tabel `rambu_pasang`

Tabel ini adalah "jembatan" yang menghubungkan sebuah SPK dengan rambu-rambu yang tercakup di dalamnya. Setiap baris merepresentasikan satu baris pekerjaan terhadap satu rambu, dalam konteks satu SPK tertentu.

| Kolom | Tipe | Keterangan |
|---|---|---|
| `rambu_spk_id` | foreign key ke `spk`, **cascade**, terindeks | Ikut terhapus apabila SPK-nya dihapus (walaupun dalam praktiknya SPK sendiri tidak pernah benar-benar dihapus lewat aplikasi) |
| `rambu_id` | foreign key ke `rambu`, **restrict**, terindeks | Rambu tidak boleh dihapus selama masih dirujuk oleh baris ini |
| `laporan_kondisi_id` | foreign key ke `laporan_kondisi`, nullable, **restrict** | Terisi apabila SPK ini dibuat berdasarkan sebuah temuan kondisi |
| `jenis_pekerjaan` | string | Bernilai `pasang_baru` atau `perbaikan`, mengikuti enum `JenisPekerjaan`, dipilih secara independen untuk setiap baris, bukan diwariskan dari kolom apa pun pada tabel `spk` |
| `jumlah` | unsigned int, default `1` | |
| `foto_survei` | string, nullable | |
| `catatan_instruksi` | string, nullable | |
| `catatan_pembatalan` | string, nullable | Alasan pembatalan, terisi apabila admin membatalkan rambu ini secara individual, bukan lewat pembatalan seluruh SPK |
| `status` | string, default `belum`, terindeks | Bernilai `belum`, `urgent`, `tertunda`, `menunggu_validasi`, `revisi`, `selesai`, atau `batal`, mengikuti enum `StatusRambuPasang` |

Relasi yang dimiliki tabel ini adalah `belongsTo` menuju `spk`, `rambu`, dan `laporan_kondisi`, ditambah `hasMany` menuju `laporan_pengerjaan` dan `kendala`.

Hanya baris `rambu_pasang` yang statusnya masih `belum`, `urgent`, atau `revisi` yang bisa diubah kembali secara bebas lewat halaman Edit Surat, baik itu mengubah jenis pekerjaannya, rambu yang dirujuk, lokasi, koordinat, jumlah, maupun membatalkannya sebagai satu baris tunggal. Begitu statusnya beranjak ke `tertunda` atau `menunggu_validasi`, yang berarti sudah ada kendala atau laporan pengerjaan nyata yang tercatat atasnya, atau bahkan sudah mencapai `selesai`, baris tersebut menjadi terkunci dari perubahan lebih lanjut, baik di sisi tampilan maupun di sisi server yang memeriksa ulang status ini sebelum benar-benar menyimpan perubahan apa pun. Penjelasan lengkap tentang alasan di balik penguncian ini tersedia pada bagian [Jenis Pekerjaan Ditentukan Per Baris Rambu, Bukan Per SPK](ALUR-BISNIS.md#jenis-pekerjaan-ditentukan-per-baris-rambu-bukan-per-spk) di dalam ALUR-BISNIS.md.

## Tabel `dikerjakan_oleh`

Tabel penghubung (pivot) antara petugas dan SPK. Tabel ini hanya memiliki kolom `created_at` (`const UPDATED_AT = null`), karena baris di dalamnya memang tidak pernah diedit setelah dibuat, hanya dibuat atau dihapus.

| Kolom | Tipe | Keterangan |
|---|---|---|
| `by_spk_id` | foreign key ke `spk`, **cascade** | |
| `by_user_id` | foreign key ke `users`, **restrict**, terindeks | |
| `is_perwakilan` | boolean, default `false` | Menandai perwakilan tim, satu-satunya yang berwenang mendaftarkan atau menambah anggota, serta mengajukan Laporan Akhir |

Ada sebuah **kunci unik komposit** pada kombinasi `(by_spk_id, by_user_id)`, yang memastikan satu petugas hanya bisa memiliki satu baris keanggotaan untuk satu SPK tertentu. Kunci unik ini bukan sekadar indeks untuk mempercepat pencarian, melainkan sebuah penjaga integritas data yang sesungguhnya. Method `daftarkanTim()` dan `tambahAnggota()` memang sudah memeriksa terlebih dahulu apakah baris yang ingin ditambahkan sudah ada lewat kode PHP biasa, namun pemeriksaan semacam ini secara teori masih memiliki celah kondisi balapan (*race condition*) apabila dua permintaan datang hampir bersamaan dari dua pengguna berbeda. Kunci unik pada level basis data inilah yang benar-benar mencegah munculnya baris duplikat, terlepas dari apa pun yang mungkin terlewat pada pemeriksaan di level aplikasi.

## Tabel `laporan_pengerjaan`

Tabel ini menyimpan laporan hasil kerja petugas untuk satu baris `rambu_pasang` tertentu.

| Kolom | Tipe | Keterangan |
|---|---|---|
| `rambu_pasang_id` | foreign key ke `rambu_pasang`, **cascade**, terindeks | |
| `dilaporkan_oleh` | foreign key ke `users`, **restrict** | |
| `foto_sesudah` | string, nullable | |
| `koordinat_gps` | string, nullable | |
| `catatan_lapangan` | string, nullable | |
| `status` | string, default `diajukan` | Bernilai `diajukan`, `diterima`, atau `ditolak`, mengikuti enum `StatusLaporan` |
| `catatan_penolakan` | string, nullable | Wajib diisi admin apabila laporan ini ditolak |
| `divalidasi_oleh` | foreign key ke `users`, nullable, **restrict** | |
| `divalidasi_pada` | timestamp, nullable | |

Relasi yang dimiliki tabel ini adalah `belongsTo` menuju `rambu_pasang` dan `users` (sekaligus merujuk ke pelapor maupun validatornya), serta `hasMany` menuju `barang_bahan`.

Satu baris `rambu_pasang` bisa memiliki **lebih dari satu** baris `laporan_pengerjaan` sepanjang waktu, apabila laporan pertama ditolak lalu direvisi dan diajukan kembali oleh petugas. Riwayat penolakan tidak pernah ditimpa oleh laporan baru, melainkan tersimpan sebagai baris tersendiri yang terpisah. Baris `laporan_pengerjaan` yang masih aktif (belum digantikan oleh revisi berikutnya) bisa diedit langsung di tempat, selama SPK yang menaunginya belum mengajukan Laporan Akhir, lewat formulir Laporan Pengerjaan yang sama.

## Tabel `barang_bahan`

Menyimpan daftar barang atau bahan material yang digunakan dalam satu laporan pengerjaan tertentu. Tabel ini juga hanya memiliki kolom `created_at`.

| Kolom | Tipe |
|---|---|
| `laporan_pengerjaan_id` | foreign key ke `laporan_pengerjaan`, **cascade** |
| `nama` | string |
| `jumlah` | unsigned int |
| `satuan` | string |

## Tabel `kendala`

Menyimpan laporan kendala lapangan untuk satu baris `rambu_pasang` tertentu.

| Kolom | Tipe | Keterangan |
|---|---|---|
| `rambu_pasang_id` | foreign key ke `rambu_pasang`, **cascade**, terindeks | |
| `dilaporkan_oleh` | foreign key ke `users`, **restrict** | |
| `alasan` | string, **wajib diisi** | |
| `foto` | string, nullable | |

## Tabel `laporan_kondisi`

Menyimpan temuan kondisi rambu rusak, sepenuhnya independen dari SPK aktif mana pun yang sedang berjalan.

| Kolom | Tipe | Keterangan |
|---|---|---|
| `rambu_id` | foreign key ke `rambu`, **restrict** | |
| `dilaporkan_oleh` | foreign key ke `users`, **restrict** | |
| `kondisi_dilaporkan` | string | |
| `foto` | string, nullable | |
| `catatan` | string, nullable | |
| `status_tindak_lanjut` | string, default `baru` | Bernilai `baru`, `sudah_dibuatkan_spk`, atau `ditolak`, mengikuti enum `StatusTindakLanjut` |
| `ditindaklanjuti_oleh` | foreign key ke `users`, nullable, **restrict** | |

## Tabel `contact_person`

Menyimpan kontak RT atau perwakilan warga per SPK, digunakan untuk keperluan tanda tangan manual di atas kertas. Tabel ini juga hanya memiliki kolom `created_at`.

| Kolom | Tipe |
|---|---|
| `nama_lengkap` | string |
| `no_telepon` | string, nullable |
| `contact_person_spk_id` | foreign key ke `spk`, **cascade** |

## Tabel `audit_log`

Menyimpan jejak setiap aksi bisnis penting yang terjadi di dalam sistem. Tabel ini juga hanya memiliki kolom `created_at`, bersifat *append-only*, artinya baris yang sudah tersimpan tidak pernah diubah lagi setelahnya (tidak ada kolom `updated_at` sama sekali).

| Kolom | Tipe | Keterangan |
|---|---|---|
| `user_id` | foreign key ke `users`, **restrict** | Siapa yang melakukan aksi tersebut |
| `spk_id` | foreign key ke `spk`, nullable, **set null**, terindeks | Apabila SPK yang dirujuk suatu saat dihapus (skenario yang berada di luar penggunaan normal aplikasi), baris log ini tidak ikut hilang, hanya kolom referensinya yang di-null-kan |
| `aksi` | string | Contohnya `spk_dibuat`, `laporan_dikirim`, `validasi_diterima`, `spk_dibatalkan`, `spk_diedit`, `rambu_pasang_dibatalkan`, `rambu_pasang_dihapus`, `temuan_ditolak`, `deadline_disesuaikan` (dipicu otomatis lewat `PenyesuaianDeadlineSpk`), dan `deadline_diperpanjang` (dipicu manual oleh admin saat menolak validasi, lihat penjelasan lengkap di [ALUR-BISNIS.md](ALUR-BISNIS.md)) |
| `tabel_terkait`, `record_id_terkait` | nullable | Belum dipakai secara konsisten di dalam kode saat ini |
| `keterangan` | string, nullable | Deskripsi singkat tentang aksi yang terjadi |

## Tabel `notifikasi`

Menyimpan notifikasi in-app untuk setiap pengguna. Tabel ini juga hanya memiliki kolom `created_at`.

| Kolom | Tipe | Keterangan |
|---|---|---|
| `user_id` | foreign key ke `users`, **restrict**, terindeks | Kolom ini menjadi bagian dari kueri yang paling sering dijalankan di seluruh sistem, karena lencana notifikasi yang belum dibaca ditampilkan di hampir setiap halaman |
| `judul` | string | |
| `pesan` | string | |
| `url` | string, nullable | Tautan tujuan apabila notifikasi ini memiliki halaman terkait yang relevan, membuat kartu notifikasinya bisa langsung diklik untuk membuka halaman tersebut. Sebagian notifikasi, misalnya penolakan temuan, sengaja tidak memiliki `url` sama sekali apabila memang tidak ada halaman yang cocok untuk dituju |
| `foto` | string, nullable | Path foto yang relevan pada saat notifikasi ini dibuat, misalnya foto sesudah atau foto kendala saat sebuah laporan diterima atau ditolak, atau foto temuan kondisi. Nilai ini dibekukan sesuai keadaan pada saat itu, dan dipakai untuk melampirkan foto pada pesan Telegram, bukan untuk ditampilkan di halaman Notifikasi in-app |
| `dibaca` | boolean, default `false` | |

Setiap baris `notifikasi` yang dibuat untuk seorang pengguna yang sudah menghubungkan akun Telegram-nya akan secara otomatis memicu pengiriman pesan yang sama lewat bot, lewat mekanisme `NotifikasiObserver`, tanpa perlu mengubah kode apa pun di titik-titik yang menciptakan notifikasi tersebut. Ini adalah contoh yang baik dari pemisahan tanggung jawab (*separation of concerns*): kode yang membuat notifikasi tidak perlu tahu atau peduli tentang bagaimana notifikasi itu akhirnya disampaikan ke pengguna.

## Tabel `system_error_log`

Menyimpan exception yang tidak terduga, yang tertangkap secara otomatis oleh penangan exception global aplikasi (`bootstrap/app.php`). Tabel ini juga hanya memiliki kolom `created_at`.

| Kolom | Tipe | Keterangan |
|---|---|---|
| `level` | string | Bernilai `info`, `warning`, `error`, atau `critical`, mengikuti enum `ErrorLevel` |
| `pesan` | string | |
| `detail` | text, nullable | Menyimpan stack trace lengkap |
| `endpoint` | string, nullable | URL yang sedang diakses pada saat error terjadi |
| `user_id` | foreign key ke `users`, nullable, **restrict** | |

Perlu ditegaskan bahwa hanya exception yang benar-benar merupakan "kesalahan sungguhan" yang tercatat di tabel ini. Kegagalan validasi input, pengalihan (redirect) karena belum login, halaman tidak ditemukan (404), dan berbagai kesalahan HTTP di bawah kode 500 sengaja **tidak** dicatat di sini, bukan karena terlewat atau bug, melainkan karena hal-hal tersebut memang bukan "kesalahan sistem" dalam pengertian teknis yang sesungguhnya.

---

## Enum

Setiap kolom yang menyimpan status atau jenis di seluruh tabel di atas secara fisik disimpan sebagai teks biasa (string) di dalam basis data. Namun, begitu data itu diakses lewat model Eloquent, ia selalu diubah bentuknya (cast) menjadi sebuah [PHP backed enum](../app/Enums). Ini berarti di dalam kode aplikasi, nilai-nilai ini selalu berupa objek enum yang aman secara tipe data, bukan sekadar string mentah yang rawan salah ketik. Berikut adalah daftar lengkap seluruh enum yang dipakai di dalam sistem beserta nilai-nilainya masing-masing.

| Enum | Nilai-Nilai yang Mungkin |
|---|---|
| `Role` | `admin`, `user` |
| `Kelamin` | `L`, `P` |
| `Urgensi` | `rendah`, `sedang`, `tinggi` |
| `StatusSpk` | `aktif`, `selesai`, `dibatalkan` |
| `StatusRambuPasang` | `belum`, `urgent`, `tertunda`, `menunggu_validasi`, `revisi`, `selesai`, `batal` |
| `KondisiRambu` | `baik`, `rusak` |
| `StatusLaporan` | `diajukan`, `diterima`, `ditolak` |
| `JenisPekerjaan` | `pasang_baru`, `perbaikan` |
| `StatusTindakLanjut` | `baru`, `sudah_dibuatkan_spk`, `ditolak` |
| `AsalPermintaan` | `internal`, `laporan_masyarakat`, `instruksi_pemerintah`, `evaluasi_petugas`, `program_kinerja` |
| `ErrorLevel` | `info`, `warning`, `error`, `critical` |
| `BentukIkon` | `bulat`, `kotak` |

## Trait `ComposesWilayah`

Trait ini dipakai bersama oleh model `Spk` dan `Rambu` (berkasnya berada di `app/Concerns/ComposesWilayah.php`), dan bekerja lewat event `saving` milik Eloquent. Cara kerjanya sederhana namun cukup elegan: apabila kolom `wilayah` sedang kosong, dan salah satu dari `jalan`, `rt`, atau `kelurahan` sudah terisi, kolom `wilayah` akan otomatis disusun menjadi sebuah teks gabungan seperti `"Jl. {jalan} RT. {rt} Kel. {kelurahan}"`.

Manfaat dari pendekatan ini cukup besar bagi keberlangsungan pengembangan sistem ke depannya. Ia memungkinkan formulir Buat SPK dan Edit SPK memakai field-field yang terstruktur dan lebih mudah divalidasi, tanpa perlu mengubah kode di sekitar dua puluh delapan tempat lain yang sudah terlanjur membaca, mencari, atau mengelompokkan (`groupBy`) data berdasarkan kolom `wilayah` sebagai teks polos. Dengan kata lain, trait ini menjembatani dua kebutuhan yang saling bertentangan, yaitu input data yang terstruktur dan rapi di satu sisi, dengan kompatibilitas terhadap kode lama yang sudah bergantung pada representasi teks di sisi lain, tanpa harus mengorbankan salah satunya.
