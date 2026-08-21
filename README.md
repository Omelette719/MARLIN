# Sistem MARLIN: Manajemen Rambu Lalu Lintas

## Sekilas Tentang Dokumen Ini

Dokumen yang sedang Anda baca ini adalah pintu masuk utama untuk memahami Sistem MARLIN secara menyeluruh, mulai dari alasan sistem ini dibangun, bagaimana ia dirancang, teknologi apa saja yang menopangnya di balik layar, sampai langkah-langkah konkret untuk menjalankannya di komputer Anda sendiri. Kami sengaja menulisnya secara panjang dan mendalam, bukan sekadar daftar poin singkat, karena MARLIN bukan aplikasi yang sederhana. Ia menyimpan siklus hidup pekerjaan nyata milik Dinas Perhubungan, mulai dari surat perintah kerja dibuat di meja admin, sampai seorang petugas berdiri di pinggir jalan memotret rambu yang baru saja dipasangnya. Memahami sistem ini dengan baik berarti memahami cerita di balik setiap baris kode, bukan hanya menghafal nama-nama tabel dan rute.

Jika Anda baru pertama kali membuka proyek ini, silakan baca dokumen ini dari atas ke bawah terlebih dahulu. Setelah itu, folder [`docs/`](docs/README.md) menyediakan pembahasan yang jauh lebih terperinci untuk setiap topik: aturan bisnis di balik siklus SPK, skema basis data lengkap dengan alasan di balik setiap keputusan kolom, penjelasan tiap halaman dan siapa yang boleh mengaksesnya, sampai catatan keamanan yang menjelaskan mana yang benar-benar celah dan mana yang memang keputusan desain yang disengaja.

---

## Daftar Isi

- [Latar Belakang dan Motivasi Pembangunan Sistem](#latar-belakang-dan-motivasi-pembangunan-sistem)
- [Mengenal Dua Peran Pengguna](#mengenal-dua-peran-pengguna)
- [Memahami Alur Kerja SPK dari Awal Hingga Akhir](#memahami-alur-kerja-spk-dari-awal-hingga-akhir)
- [Rincian Fitur per Peran](#rincian-fitur-per-peran)
  - [Fitur untuk Admin](#fitur-untuk-admin)
  - [Fitur untuk Petugas Lapangan](#fitur-untuk-petugas-lapangan)
  - [Fitur yang Dipakai Bersama](#fitur-yang-dipakai-bersama)
- [Rancangan Struktur Basis Data](#rancangan-struktur-basis-data)
- [Tumpukan Teknologi yang Dipakai](#tumpukan-teknologi-yang-dipakai)
- [Cara Menjalankan Sistem Setelah Clone](#cara-menjalankan-sistem-setelah-clone)
- [Akun yang Tersedia Setelah Seeding](#akun-yang-tersedia-setelah-seeding)
- [Menjalankan Rangkaian Pengujian (Test)](#menjalankan-rangkaian-pengujian-test)
- [Peta Struktur Direktori Proyek](#peta-struktur-direktori-proyek)
- [Proses Integrasi dan Pengujian Otomatis (CI/CD)](#proses-integrasi-dan-pengujian-otomatis-cicd)
- [Ke Mana Selanjutnya: Dokumentasi Lengkap](#ke-mana-selanjutnya-dokumentasi-lengkap)

---

## Latar Belakang dan Motivasi Pembangunan Sistem

Sebelum MARLIN ada, pengelolaan rambu lalu lintas di lingkungan Dinas Perhubungan Kota Banjarmasin berjalan dengan cara yang sangat bergantung pada koordinasi manual. Ketika ada permintaan pemasangan rambu baru, entah datang dari laporan masyarakat, instruksi pemerintah, atau hasil evaluasi internal petugas sendiri, prosesnya biasanya dimulai dari percakapan lisan atau pesan singkat di grup WhatsApp. Surat perintah kerja diketik ulang setiap kali dari nol, meskipun formatnya sebenarnya selalu serupa. Tidak ada satu tempat pun yang menyimpan riwayat kondisi setiap rambu secara terpusat, sehingga ketika seseorang bertanya "rambu di jalan ini sudah diperbaiki belum ya," jawabannya sering kali harus dicari lewat percakapan lama atau bertanya langsung ke petugas yang kebetulan ingat.

Masalah ini terdengar sederhana di permukaan, tetapi dampaknya nyata. Admin kesulitan memantau berapa banyak surat perintah kerja yang sedang berjalan, mana yang sudah lewat tenggat waktu, dan mana yang butuh perhatian segera. Petugas lapangan tidak punya cara mudah untuk mengetahui pekerjaan baru apa saja yang tersedia untuk mereka ambil, atau melihat riwayat pekerjaan mereka sendiri sebagai bukti kerja. Rambu yang rusak dan ditemukan secara tidak sengaja saat patroli sering kali tidak tercatat dengan rapi, sehingga bisa terlewat begitu saja sampai ada yang melapor ulang.

Dari situlah MARLIN, singkatan dari Manajemen Rambu Lalu Lintas, dirancang dan dibangun. Filosofi utamanya sederhana: jadikan satu sistem sebagai sumber kebenaran tunggal (*single source of truth*) untuk segala sesuatu yang berkaitan dengan siklus hidup rambu lalu lintas di kota ini. Bukan hanya mencatat data, tetapi benar-benar mengalirkan proses kerja yang sebenarnya terjadi di lapangan ke dalam sistem, mulai dari surat dibuat, sampai rambu benar-benar berdiri dan divalidasi kondisinya oleh admin.

Secara konkret, sistem ini menjadi tempat tunggal untuk:

- Menyimpan daftar seluruh rambu yang ada, lengkap dengan lokasi (dinyatakan dalam bentuk koordinat lintang-bujur), kondisi terkininya, dan riwayat foto dari waktu ke waktu.
- Mengelola siklus Surat Perintah Kerja (SPK), mulai dari dibuat oleh admin, dikerjakan oleh tim petugas di lapangan, sampai divalidasi hasilnya dan akhirnya diarsipkan sebagai selesai atau dibatalkan.
- Menampung laporan kendala di lapangan (misalnya ketika pemasangan tidak bisa dilanjutkan karena suatu sebab), sekaligus laporan temuan kondisi rusak yang bisa dilaporkan kapan saja, tanpa harus terikat pada surat perintah kerja yang sedang aktif.
- Menyajikan peta interaktif yang menampilkan seluruh rambu sebagai titik-titik berwarna, di mana warna tersebut langsung mencerminkan status pekerjaan dan kondisi rambu secara nyaris real-time.
- Mencatat jejak audit: siapa melakukan apa, kapan, dan terhadap surat perintah kerja yang mana, sehingga ketika suatu saat ada pertanyaan atau bahkan sengketa tentang sebuah keputusan, jawabannya bisa ditelusuri kembali dari data, bukan dari ingatan seseorang.
- Menghasilkan laporan bulanan dalam bentuk PDF yang siap dicetak dan diserahkan kepada atasan, tanpa perlu menyusun ulang data secara manual setiap bulannya.

Dengan kata lain, MARLIN tidak dibangun untuk menggantikan proses birokrasi persetujuan permintaan rambu yang memang sudah berjalan di luar sistem (itu tetap menjadi urusan administratif manual antara Dinas Perhubungan dan pemohon), melainkan untuk mengambil alih dan merapikan segala sesuatu yang terjadi *setelah* sebuah permintaan disetujui, sampai pekerjaan itu selesai secara nyata di lapangan dan tercatat dengan baik.

## Mengenal Dua Peran Pengguna

Sistem ini dirancang dengan model peran yang sengaja dibuat sesederhana mungkin, hanya dua jenis pengguna, karena struktur organisasi yang sebenarnya di lapangan memang hanya membedakan dua kelompok orang: mereka yang mengatur pekerjaan dari balik meja, dan mereka yang mengerjakannya secara fisik di jalanan. Kesederhanaan ini bukan keterbatasan, melainkan keputusan desain yang disengaja, karena menambahkan lapisan peran yang lebih rumit tanpa kebutuhan nyata hanya akan membuat sistem lebih sulit dipahami tanpa manfaat yang sepadan.

Kolom `role` pada tabel `users` menyimpan dua kemungkinan nilai berikut.

| Peran | Siapa Mereka | Apa yang Bisa Mereka Lakukan |
|---|---|---|
| `admin` | Staf Dinas Perhubungan yang bertanggung jawab mengatur pekerjaan | Membuat dan mengelola Surat Perintah Kerja, memvalidasi hasil kerja yang dilaporkan petugas, mengelola data master seperti jenis rambu, serta mengatur akun pengguna lain |
| `user` (disebut juga petugas lapangan dalam percakapan sehari-hari) | Tim yang benar-benar turun ke jalan untuk memasang atau memperbaiki rambu | Bergabung ke Surat Perintah Kerja yang tersedia, mengirimkan laporan hasil pekerjaan lengkap dengan foto dan koordinat GPS, melaporkan kendala yang dihadapi di lapangan, serta melaporkan temuan rambu rusak yang mereka lihat sendiri |

Setiap kelompok rute (route) dalam aplikasi dijaga secara ketat oleh middleware bernama `EnsureUserHasRole`, yang bisa dikonfigurasi untuk hanya mengizinkan peran `admin` atau hanya peran `user`. Yang menarik dari implementasi ini adalah bagaimana ia menangani percobaan akses yang salah peran. Alih-alih menampilkan halaman error yang dingin dan generik, sistem justru dengan halus mengarahkan pengguna kembali ke dashboard sesuai dengan perannya sendiri. Efeknya secara psikologis cukup penting: seorang petugas yang mencoba membuka halaman khusus admin tidak akan melihat pesan "Anda tidak memiliki akses," melainkan seakan-akan halaman itu memang tidak pernah ada. Ini adalah pilihan desain keamanan yang sengaja, mengurangi informasi yang bisa dimanfaatkan seseorang untuk memetakan struktur internal sistem hanya dengan mencoba-coba URL.

Proses masuk ke sistem (login) menggunakan Nomor Induk Pegawai (NIP) sebagai identitas utama, bukan alamat surel seperti kebanyakan aplikasi pada umumnya. Keputusan ini dikonfigurasi lewat berkas `config/fortify.php`, tepatnya pada baris `'username' => 'nip'`, dan masuk akal secara konteks karena NIP adalah identitas yang sudah dimiliki dan diingat oleh setiap pegawai Dinas Perhubungan, sementara alamat surel belum tentu digunakan secara konsisten oleh seluruh staf, apalagi oleh petugas lapangan yang lebih sering berinteraksi dengan ponsel di tengah pekerjaan fisik ketimbang di depan komputer kantor. Sebagai lapisan keamanan tambahan, sistem juga menyediakan Autentikasi Dua Faktor (2FA) yang sifatnya opsional per akun, sehingga setiap pengguna dapat memilih sendiri apakah ingin mengaktifkannya atau tidak, tergantung pada kebutuhan dan tingkat kenyamanan masing-masing.

## Memahami Alur Kerja SPK dari Awal Hingga Akhir

Jantung dari seluruh sistem ini adalah siklus hidup Surat Perintah Kerja, yang biasa disingkat SPK. Memahami siklus ini dengan baik adalah kunci untuk memahami hampir seluruh fitur lain yang ada di dalam sistem, karena hampir semua bagian aplikasi pada akhirnya berputar mengelilingi konsep ini. Berikut adalah gambaran garis besarnya, disusun secara berurutan seperti sebuah cerita yang mengalir dari awal sampai akhir.

Semuanya dimulai ketika seorang admin membuat sebuah SPK baru. Dalam satu surat, admin dapat memasukkan banyak baris rambu sekaligus, dan yang menarik, setiap baris rambu di dalam satu surat yang sama boleh memiliki jenis pekerjaannya sendiri-sendiri, baik itu **Pemasangan Baru** untuk rambu yang belum pernah ada secara fisik, maupun **Perbaikan** untuk rambu yang sudah terdaftar namun kondisinya perlu dibenahi. Artinya, satu SPK bisa saja berisi campuran: satu baris untuk memasang rambu baru di suatu perempatan, dan baris lainnya untuk memperbaiki rambu larangan yang sudah rusak di jalan lain, semuanya tercakup dalam satu dokumen surat yang sama.

Setelah SPK tercipta dan berstatus aktif, giliran petugas lapangan yang berperan. Salah satu dari mereka, yang bertindak sebagai perwakilan tim, mendaftarkan diri sekaligus rekan-rekan setimnya untuk mengerjakan surat tersebut. Begitu tim resmi bergabung, mereka pun turun ke lokasi untuk mengerjakan setiap rambu yang tercantum dalam surat itu satu per satu.

Di setiap titik rambu, ada dua kemungkinan jalan cerita. Jika pekerjaan berjalan lancar tanpa hambatan, perwakilan tim mengisi Form Laporan Pengerjaan, yang mewajibkan foto hasil akhir pekerjaan beserta koordinat GPS lokasi sebenarnya, ditambah catatan lapangan dan daftar barang atau bahan yang digunakan jika relevan. Namun jika ternyata pekerjaan itu tidak bisa dilanjutkan karena suatu sebab, misalnya lokasi ternyata sudah ada rambu lain, atau kondisi jalan tidak memungkinkan, perwakilan tim mengisi Form Kendala sebagai gantinya, lengkap dengan foto dan alasan yang wajib dijelaskan.

Setelah seluruh rambu dalam satu SPK sudah tertangani, entah lewat laporan pengerjaan maupun lewat laporan kendala, perwakilan tim dapat mengajukan sesuatu yang disebut Laporan Akhir. Inilah gerbang yang memasukkan SPK tersebut ke dalam antrean validasi milik admin. Alasan mengapa validasi tidak langsung terjadi begitu satu laporan pengerjaan masuk, melainkan menunggu seluruh SPK diajukan sebagai batch, dijelaskan lebih mendalam di [docs/ALUR-BISNIS.md](docs/ALUR-BISNIS.md), namun intinya sederhana: admin tidak perlu bolak-balik memvalidasi rambu yang baru sebagian selesai, cukup meninjau satu SPK secara utuh dalam satu waktu.

Ketika admin membuka halaman validasi, mereka meninjau setiap rambu satu per satu dan memutuskan apakah hasil kerjanya diterima atau ditolak. Rambu yang statusnya masih tertunda karena adanya kendala tidak pernah bisa "diterima" begitu saja, karena secara logis memang belum ada pekerjaan nyata yang bisa dianggap selesai untuknya, ia selalu diarahkan ke jalur revisi. Sementara itu, rambu yang laporannya ditolak akan kembali berstatus revisi disertai catatan penjelasan dari admin tentang apa yang perlu diperbaiki, dan petugas mengerjakan ulang khusus rambu tersebut tanpa mengganggu rambu lain yang sudah diterima dalam SPK yang sama.

Begitu seluruh rambu dalam satu SPK sudah berstatus selesai atau dibatalkan, secara otomatis status SPK itu sendiri berubah menjadi selesai, dan sistem mencatat tanggal penyelesaiannya untuk keperluan analitik seperti menghitung berapa lama sebuah pekerjaan berlangsung dan apakah selesai lebih cepat atau lebih lambat dari tenggat waktu yang direncanakan.

Tingkat urgensi sebuah SPK, yang ditampilkan sebagai Rendah, Sedang, atau Tinggi, dihitung sepenuhnya secara otomatis oleh sistem berdasarkan sisa hari menuju tenggat waktu. Semakin dekat tenggat waktunya, semakin tinggi urgensinya, kecuali admin secara eksplisit menandai sebuah SPK sebagai prioritas, yang otomatis membuat urgensinya menjadi Tinggi tanpa memandang berapa lama sisa waktunya.

Sebagai pelengkap dari alur utama di atas, sistem juga menyediakan jalur pelaporan yang sepenuhnya terpisah, yaitu Lapor Temuan Kondisi. Fitur ini memungkinkan siapa saja yang sedang login, baik admin maupun petugas, untuk melaporkan rambu yang mereka temukan dalam kondisi rusak, kapan saja dan di mana saja, tanpa harus terikat pada SPK aktif tertentu yang sedang mereka kerjakan. Laporan semacam ini masuk ke antrean tersendiri yang bisa ditindaklanjuti admin dengan membuat SPK perbaikan baru, atau ditolak jika memang belum perlu ditindaklanjuti.

## Rincian Fitur per Peran

Bagian ini merangkum fitur-fitur utama yang tersedia untuk setiap peran. Untuk penjelasan yang jauh lebih rinci tentang setiap fitur, termasuk aturan validasi dan alasan di balik setiap keputusan desainnya, silakan buka [docs/FITUR.md](docs/FITUR.md).

### Fitur untuk Admin

Seorang admin membuka sistem dan langsung disambut oleh **Dashboard**, yang menyajikan ringkasan angka-angka penting: berapa SPK yang sedang aktif, berapa rambu yang sedang dalam kondisi rusak, dan berapa laporan yang menunggu validasi. Dashboard ini juga dilengkapi dengan widget peta ringkas yang punya filternya sendiri, lengkap dengan tombol untuk mengunduh laporan sebaran rambu dalam bentuk PDF, komplit dengan cuplikan gambar peta yang sedang ditampilkan.

Untuk membuat pekerjaan baru, admin menggunakan halaman **Buat Surat**, tempat mereka menyusun sebuah SPK lengkap dengan alamat yang terstruktur (dipecah menjadi Jalan, RT, dan Kelurahan agar lebih rapi dan bisa diproses secara otomatis), tenggat waktu, dan daftar rambu yang ingin dikerjakan. Setiap baris rambu dalam surat ini bisa memilih sendiri apakah ia sebuah pemasangan baru atau sebuah perbaikan terhadap rambu yang sudah terdaftar sebelumnya.

Halaman **Daftar Surat** menampilkan seluruh SPK yang masih berstatus aktif, sementara **Riwayat SPK** menyimpan arsip untuk SPK yang sudah selesai maupun yang dibatalkan, memastikan kedua daftar ini tidak saling bercampur sehingga admin selalu bisa fokus pada pekerjaan yang memang masih berjalan.

Ketika laporan dari petugas mulai berdatangan, admin menindaklanjutinya lewat halaman **Validasi Pengerjaan**, tempat mereka meninjau setiap rambu dalam SPK yang telah mengajukan Laporan Akhir, lalu memutuskan diterima atau ditolak satu per satu.

Untuk kebutuhan pelaporan ke atasan, tersedia **Laporan Bulanan** dan **Laporan Rambu**, keduanya bisa difilter berdasarkan rentang tanggal, jenis rambu, dan status, lalu diunduh sebagai berkas PDF yang siap cetak.

Admin juga bertanggung jawab mengelola data pendukung: **Kelola Jenis Rambu** untuk mengatur kategori-kategori rambu yang tersedia, **Kelola Rambu** untuk melihat seluruh aset rambu fisik yang terdaftar, dan **Kelola Pengguna** untuk menambah akun baru atau menonaktifkan akun yang sudah tidak aktif lagi. Sebagai penutup, halaman **Temuan Kondisi Belum Ditindaklanjuti** menampung laporan kerusakan yang dilaporkan petugas namun belum diproses jadi SPK, sementara **Audit Log** dan **System Error Log** masing-masing menyimpan jejak aksi bisnis dan jejak kesalahan teknis yang terjadi di dalam sistem.

### Fitur untuk Petugas Lapangan

Bagi seorang petugas lapangan, halaman yang pertama kali muncul setelah login adalah **Daftar Surat Aktif**, yang menampilkan *seluruh* SPK yang sedang berstatus aktif, bukan hanya yang sudah mereka ikuti. Ini disengaja, karena halaman inilah yang menjadi tempat petugas menemukan pekerjaan baru untuk diambil, sebuah "papan pekerjaan" yang selalu terbuka.

Ketika seorang petugas menemukan SPK yang menarik untuk dikerjakan, mereka membuka **Detail SPK** untuk melihat rincian lengkapnya, lalu mendaftarkan diri sebagai perwakilan tim, sekaligus mengajak rekan-rekan setim yang lain untuk bergabung dalam satu langkah yang sama.

Selama pekerjaan berlangsung, perwakilan tim menggunakan **Form Laporan Pengerjaan** untuk melaporkan hasil kerja yang sukses, atau **Form Kendala** ketika ada hambatan yang membuat pekerjaan tidak bisa diselesaikan saat itu juga. Setelah seluruh rambu dalam SPK tertangani, mereka mengajukan Laporan Akhir untuk memasukkan surat itu ke antrean validasi admin.

Di luar konteks SPK yang sedang dikerjakan, setiap petugas juga bisa menggunakan **Lapor Temuan Kondisi** kapan saja mereka menemukan rambu rusak, bahkan ketika sedang tidak mengerjakan SPK apa pun. Untuk melihat rekam jejak pekerjaan mereka sendiri, tersedia halaman **Riwayat Pekerjaan Saya**, yang bisa difilter berdasarkan bulan sebagai bukti kerja yang bisa ditunjukkan kepada atasan bila diperlukan.

### Fitur yang Dipakai Bersama

Beberapa fitur tidak terikat pada satu peran saja, melainkan bisa diakses oleh siapa pun yang sudah login ke dalam sistem. Yang paling menonjol tentu saja **Peta Interaktif**, dibangun di atas pustaka Leaflet dan menggunakan data peta terbuka dari OpenStreetMap, menampilkan seluruh rambu sebagai titik-titik berwarna. Warna setiap titik mencerminkan tingkat urgensi SPK yang menaunginya secara langsung: merah untuk yang urgent, prioritas, atau berurgensi Tinggi, kuning untuk yang berurgensi Sedang, cyan untuk yang sedang menunggu validasi, biru tua untuk yang sudah selesai dan kondisinya baik (satu-satunya keadaan yang tidak berkedip), dan abu-abu untuk yang berurgensi Rendah atau belum memiliki tugas aktif sama sekali. Mengarahkan kursor ke sebuah titik akan menampilkan kartu informasi ringkas, yang secara pintar membuka dirinya sendiri di sisi kiri atau kanan tergantung ruang kosong yang tersedia di layar, sehingga kartu itu tidak pernah terpotong keluar dari tampilan.

Fitur **Notifikasi** memberi tahu pengguna tentang peristiwa-peristiwa penting yang relevan dengan peran mereka, ditampilkan lewat ikon lonceng di bagian header dengan lencana berwarna merah yang menunjukkan jumlah notifikasi yang belum dibaca. Bagi mereka yang ingin tetap mendapat pemberitahuan meskipun sedang tidak membuka aplikasi, sistem juga bisa dihubungkan dengan **Telegram**, sehingga notifikasi yang sama juga terkirim sebagai pesan chat.

Terakhir, halaman **Settings** menyediakan ruang bagi setiap pengguna untuk mengelola profil mereka sendiri dan pengaturan keamanan akun, termasuk mengganti kata sandi dan mengaktifkan Autentikasi Dua Faktor.

## Rancangan Struktur Basis Data

Basis data MARLIN terdiri dari tiga belas tabel domain, di luar tabel-tabel bawaan Laravel seperti `users`, `sessions`, `cache`, dan `jobs` yang sudah tersedia secara standar dari kerangka kerja itu sendiri. Tabel-tabel domain ini dirancang secara cermat untuk merefleksikan hubungan nyata antara entitas-entitas di dunia fisik: sebuah rambu adalah aset yang berdiri sendiri terlepas dari pekerjaan apa pun yang pernah menyentuhnya, sementara sebuah SPK adalah dokumen kerja yang bisa menaungi banyak rambu sekaligus.

| Tabel | Fungsinya di Dalam Sistem | Relasi yang Penting untuk Dipahami |
|---|---|---|
| `jenis_rambu` | Menyimpan data master kategori rambu, seperti nama, spesifikasi standar, gambar referensi, dan bentuk ikon yang ditampilkan di peta | Memiliki banyak (`hasMany`) baris pada tabel `rambu` |
| `rambu` | Merepresentasikan satu unit rambu fisik: jenisnya, alamatnya (baik dalam bentuk teks gabungan `wilayah` maupun field terstruktur `jalan`/`rt`/`kelurahan`), lokasi spesifiknya, koordinatnya, kondisi terkininya, dan apakah ia sudah benar-benar terpasang secara fisik | Merujuk (`belongsTo`) ke `jenis_rambu`; memiliki banyak baris pada `rambu_pasang` dan `laporan_kondisi` |
| `spk` | Merepresentasikan satu Surat Perintah Kerja: nomor suratnya, alamat, tenggat waktu, tingkat urgensi, status, asal permintaan, dan perihal | Memiliki banyak baris pada `rambu_pasang`, `dikerjakan_oleh`, `contact_person`, dan `audit_log` |
| `rambu_pasang` | Merepresentasikan satu baris pekerjaan terhadap satu rambu, dalam konteks satu SPK tertentu; ini adalah "jembatan" yang menghubungkan sebuah SPK dengan rambu-rambu yang tercakup di dalamnya | Merujuk ke `spk` dan `rambu`; memiliki banyak baris pada `laporan_pengerjaan` dan `kendala` |
| `dikerjakan_oleh` | Tabel penghubung (pivot) yang mencatat siapa saja petugas yang bergabung ke sebuah SPK, dan apakah mereka bertindak sebagai perwakilan | Merujuk ke `spk` dan `users` |
| `laporan_pengerjaan` | Menyimpan laporan hasil kerja petugas untuk satu baris `rambu_pasang`, termasuk foto hasil akhir, koordinat GPS, dan status persetujuannya | Merujuk ke `rambu_pasang` dan `users`; memiliki banyak baris pada `barang_bahan` |
| `barang_bahan` | Daftar barang atau bahan material yang digunakan dalam satu laporan pengerjaan tertentu | Merujuk ke `laporan_pengerjaan` |
| `kendala` | Menyimpan laporan kendala lapangan untuk satu baris `rambu_pasang`, lengkap dengan alasan dan foto pendukung | Merujuk ke `rambu_pasang` dan `users` |
| `laporan_kondisi` | Menyimpan temuan kondisi rusak yang dilaporkan petugas, tidak terikat pada SPK aktif mana pun, lengkap dengan status tindak lanjutnya | Merujuk ke `rambu` dan `users` |
| `contact_person` | Menyimpan kontak RT atau perwakilan warga setempat per SPK, untuk keperluan tanda tangan manual di atas kertas saat petugas berkunjung ke lapangan | Merujuk ke `spk` |
| `audit_log` | Menyimpan jejak setiap aksi bisnis penting yang terjadi di dalam sistem | Merujuk ke `users` dan `spk` (dengan `spk_id` yang di-null-kan, bukan ikut terhapus, apabila SPK sumbernya suatu saat terhapus) |
| `notifikasi` | Menyimpan notifikasi in-app untuk setiap pengguna | Merujuk ke `users` |
| `system_error_log` | Menyimpan jejak kesalahan teknis (exception) yang tertangkap otomatis oleh penangan kesalahan global aplikasi | Merujuk ke `users` secara opsional |

Ada satu prinsip perancangan yang konsisten diterapkan di seluruh skema ini terkait aturan penghapusan data lewat foreign key. Data yang secara konseptual "milik" sebuah SPK atau sebuah baris `rambu_pasang`, seperti laporan pengerjaan dan kendala, akan ikut terhapus otomatis (`cascade`) apabila induknya dihapus. Sebaliknya, data yang berfungsi sebagai referensi, seperti `rambu` itu sendiri, akun `users`, dan `laporan_kondisi`, sengaja **tidak boleh** dihapus selama masih dirujuk oleh baris lain (`restrict`), karena menghapusnya akan meninggalkan data lain dalam keadaan yatim tanpa konteks yang jelas. Dan yang paling penting, sebuah SPK **tidak pernah** dihapus secara permanen dari sistem sekali pun; ketika sebuah pekerjaan dibatalkan, yang berubah hanyalah kolom statusnya menjadi `dibatalkan`, bukan barisnya yang lenyap dari basis data. Ini adalah keputusan yang disengaja demi menjaga akuntabilitas penuh, mengingat sistem ini digunakan oleh sebuah instansi pemerintah yang harus bisa mempertanggungjawabkan setiap keputusan kerja yang pernah dibuat.

Setiap kolom yang menyimpan status atau jenis, seperti `status`, `urgensi`, `kondisi_terkini`, dan `jenis_pekerjaan`, secara fisik disimpan sebagai teks biasa (string) di dalam basis data, tetapi selalu diubah bentuknya (cast) menjadi [PHP backed enum](app/Enums) begitu data itu diakses lewat model Eloquent. Pendekatan ini memberikan yang terbaik dari dua dunia: fleksibilitas string di level penyimpanan, dan keamanan tipe data (type safety) yang ketat di level kode aplikasi, sehingga kesalahan ketik semacam menulis `"slesai"` alih-alih `"selesai"` akan langsung tertangkap sebagai kesalahan program, bukan menjadi bug tersembunyi yang baru ketahuan berbulan-bulan kemudian. Untuk penjelasan lengkap tentang setiap kolom, tipe datanya, dan seluruh nilai enum yang mungkin, silakan lihat [docs/DATABASE.md](docs/DATABASE.md).

## Tumpukan Teknologi yang Dipakai

Pemilihan teknologi untuk sistem ini didasarkan pada pertimbangan kematangan ekosistem, kemudahan pengembangan yang cepat, dan kesesuaian dengan skala penggunaan internal yang tidak memerlukan infrastruktur yang berlebihan.

| Lapisan | Teknologi yang Digunakan |
|---|---|
| Kerangka kerja backend | Laravel 13, berjalan di atas PHP versi 8.3 ke atas |
| Antarmuka reaktif | Livewire 4, dengan komponen berbasis class yang berada di `app/Livewire`, dan tampilannya di `resources/views/pages` |
| Komponen antarmuka | Flux UI 2 (tingkat gratis), dilengkapi dengan komponen kustom buatan sendiri seperti `searchable-select` dan `searchable-multiselect` untuk kebutuhan yang tidak tersedia di tingkat gratis tersebut |
| Autentikasi | Laravel Fortify, dikonfigurasi untuk login berbasis NIP, mendukung Autentikasi Dua Faktor, dan mewajibkan konfirmasi ulang kata sandi sebelum masuk ke halaman-halaman sensitif |
| Basis data | SQLite untuk lingkungan pengembangan, diakses seluruhnya lewat Eloquent ORM |
| Peta | Leaflet.js dipadukan dengan tile peta dari OpenStreetMap |
| Pembuatan PDF | Paket `barryvdh/laravel-dompdf`, digunakan untuk menghasilkan Surat Pengantar dan berbagai Laporan Bulanan |
| Gaya tampilan (CSS) | Tailwind CSS 4 |
| Alat build aset frontend | Vite |
| Pengujian otomatis | PHPUnit, dengan bantuan `Livewire::test()` khusus untuk menguji komponen-komponen Livewire secara terisolasi |

## Cara Menjalankan Sistem Setelah Clone

Bagian ini menuntun Anda langkah demi langkah untuk menyiapkan sistem ini agar bisa berjalan di komputer Anda sendiri, dimulai dari kondisi repositori yang baru saja di-clone hingga siap diakses lewat peramban web.

### Prasyarat yang Harus Sudah Terpasang

Sebelum memulai, pastikan komputer Anda sudah memiliki hal-hal berikut ini terpasang dengan benar:

- PHP versi **8.3** atau yang lebih baru, dengan ekstensi `pdo_sqlite`, `mbstring`, dan `dom` yang wajib aktif, serta ekstensi `gd` yang sangat disarankan karena dipakai oleh DomPDF untuk merender foto-foto yang ditempelkan ke dalam dokumen Surat Pengantar maupun Laporan Bulanan.
- Composer versi 2, sebagai pengelola dependensi PHP.
- Node.js versi 22 beserta npm, untuk mengelola dependensi dan proses build aset frontend.
- Dukungan SQLite yang aktif pada instalasi PHP Anda.

### Langkah-Langkah Instalasi

Ikuti urutan perintah berikut ini satu per satu.

```bash
git clone https://github.com/Omelette719/MARLIN.git
cd MARLIN

# 1. Pasang seluruh dependensi PHP maupun JavaScript
composer install
npm install

# 2. Siapkan berkas environment
cp .env.example .env
php artisan key:generate

# 3. Siapkan berkas basis data SQLite
touch database/database.sqlite
# (kalau memakai Windows/PowerShell: New-Item database/database.sqlite -ItemType File)

# 4. Jalankan migrasi sekaligus seeder dasar
php artisan migrate --seed

# 5. Buat symlink untuk folder storage, supaya foto hasil unggahan bisa diakses lewat peramban
php artisan storage:link

# 6. Build aset frontend
npm run build
# (atau gunakan `npm run dev` selama masa pengembangan aktif, untuk mendapatkan hot-reload otomatis)

# 7. Jalankan server pengembangan lokal
php artisan serve
php artisan telegram:poll
```

Setelah seluruh langkah di atas selesai, buka `http://localhost:8000` (atau domain lokal Anda sendiri kalau menggunakan Laravel Herd atau Valet), lalu login menggunakan salah satu akun yang sudah dijelaskan pada bagian [Akun yang Tersedia Setelah Seeding](#akun-yang-tersedia-setelah-seeding) di bawah ini.

Perlu diketahui bahwa berkas `database/seeders/DatabaseSeeder.php` secara bawaan hanya membuat akun-akun pengguna (lima akun admin dan satu akun petugas) beserta data master jenis rambu. Ia sengaja **tidak** membuat data contoh berupa SPK atau rambu apa pun, agar basis data awal tetap bersih. Apabila Anda membutuhkan data contoh untuk keperluan demonstrasi atau pengembangan lebih lanjut, jalankan seeder khusus berikut secara terpisah:

```bash
php artisan db:seed --class=MarlinDemoSeeder
```

Seeder ini akan menambahkan beberapa akun petugas contoh beserta notifikasi selamat datang. Data contoh untuk SPK, rambu, dan temuan kondisi untuk sementara waktu dinonaktifkan di dalam kode (lihat method `run()` pada berkas `MarlinDemoSeeder`, di mana method-method pembuat data contoh itu masih tersimpan lengkap, tinggal diaktifkan kembali apabila suatu saat dibutuhkan). Seeder ini aman dijalankan kapan pun, karena ia tidak menyentuh atau menimpa akun admin maupun petugas yang sudah ada sebelumnya.

### Mengatur Ulang Basis Data dari Awal

Jika suatu saat Anda perlu mengembalikan basis data ke kondisi kosong seperti semula dan menjalankan ulang seluruh migrasi beserta seeder-nya, gunakan perintah berikut.

```bash
php artisan migrate:fresh --seed
```

## Akun yang Tersedia Setelah Seeding

Seluruh akun yang dihasilkan lewat proses seeding menggunakan kata sandi bawaan yang sama, yaitu **`password`**.

| Peran | Nama | NIP | Keterangan Tambahan |
|---|---|---|---|
| Admin | Admin Dishub | `1` | Akun admin utama, NIP-nya sengaja dibuat sangat pendek supaya lebih mudah diketik saat proses pengembangan berlangsung |
| Admin | (empat akun lainnya) | acak, lihat langsung isi tabel `users` | Dibuat lewat factory, sehingga NIP maupun username-nya acak |
| Petugas | Test User | `2` | Satu-satunya akun petugas yang dibuat secara bawaan |

Perlu diingat, halaman login pada `/login` mengharuskan Anda memasukkan **NIP**, bukan alamat surel, sesuai dengan penjelasan pada bagian sebelumnya.

## Menjalankan Rangkaian Pengujian (Test)

Untuk menjalankan seluruh rangkaian pengujian otomatis yang sudah disusun untuk memvalidasi perilaku sistem, gunakan perintah berikut.

```bash
php artisan test
```

Cakupan pengujian ini meliputi hampir seluruh area fungsional sistem: alur autentikasi lengkap dengan login, verifikasi 2FA, dan konfirmasi ulang kata sandi; validasi pembuatan dan pengubahan SPK; proses validasi hasil pengerjaan termasuk skenario perpanjangan tenggat waktu ketika sebuah laporan ditolak; pelaporan kendala dan laporan pengerjaan dari sisi petugas; pelaporan temuan kondisi; pembuatan laporan bulanan; endpoint data JSON untuk peta; jejak audit log; sistem notifikasi; integrasi dengan Telegram; sampai proses pembuatan dokumen PDF untuk Surat Pengantar dan berbagai laporan lainnya.

Untuk memeriksa apakah gaya penulisan kode sudah konsisten dengan standar yang ditetapkan (menggunakan Laravel Pint), jalankan salah satu dari dua perintah berikut, tergantung apakah Anda hanya ingin memeriksa saja atau sekaligus memperbaikinya secara otomatis.

```bash
composer lint:check   # hanya memeriksa, tidak mengubah berkas apa pun
composer lint         # memeriksa sekaligus memperbaiki secara otomatis
```

## Peta Struktur Direktori Proyek

Untuk memudahkan Anda menavigasi kode sumber proyek ini, berikut adalah gambaran umum tentang direktori-direktori penting beserta fungsinya masing-masing.

```
app/
  Enums/            Seluruh backed enum yang dipakai sistem (status, jenis pekerjaan, urgensi, dan lain sebagainya)
  Concerns/         Trait yang dipakai bersama oleh beberapa model, misalnya ComposesWilayah yang secara otomatis
                     menyusun kolom wilayah dari kombinasi jalan/rt/kelurahan
  Http/Controllers/ Controller di luar Livewire, dipakai untuk menghasilkan dokumen PDF dan menyajikan data peta
  Livewire/
    Admin/          Kumpulan komponen yang khusus dipakai admin (Spk, Validasi, Users, Laporan, dan seterusnya)
    User/           Kumpulan komponen yang khusus dipakai petugas (Kendala, Laporan, Temuan, dan seterusnya)
    Rambu/, Settings/   Komponen yang dipakai bersama oleh kedua peran
  Models/           Model Eloquent beserta seluruh relasinya untuk setiap tabel domain
  Support/          Kumpulan logika pendukung yang bukan berupa model, misalnya untuk penyusunan Laporan Bulanan
database/
  migrations/       Satu berkas migrasi untuk setiap tabel, disusun mengikuti urutan dependensi foreign key
  seeders/          DatabaseSeeder untuk akun dasar, JenisRambuSeeder untuk data master, dan MarlinDemoSeeder
                     untuk data contoh (opsional)
resources/
  views/pages/      Berkas tampilan Blade, strukturnya mengikuti namespace komponen Livewire
  views/pdf/        Templat untuk dokumen PDF (surat pengantar, laporan bulanan)
  js/app.js         Inisialisasi peta Leaflet dan berbagai fungsi JavaScript pendukung lainnya
tests/Feature/      Berkas pengujian per fitur, dikelompokkan dalam folder Admin/, User/, dan lainnya
```

## Proses Integrasi dan Pengujian Otomatis (CI/CD)

Setiap kali ada perubahan yang di-push atau pull request yang dibuka ke cabang `main`, dua alur kerja GitHub Actions berikut ini akan otomatis berjalan, keduanya bisa ditemukan di direktori `.github/workflows/`.

Alur kerja pertama, `tests.yml`, bertugas memasang seluruh dependensi, membangun aset frontend, lalu menjalankan seluruh rangkaian pengujian PHPUnit pada matriks tiga versi PHP sekaligus, yaitu 8.3, 8.4, dan 8.5, memastikan sistem tetap kompatibel di berbagai versi PHP yang mungkin digunakan di lingkungan produksi nanti.

Alur kerja kedua, `lint.yml`, menjalankan pemeriksaan gaya kode menggunakan Pint, menjaga konsistensi format penulisan kode di seluruh proyek.

Sebagai catatan teknis tambahan, berkas `composer.json` sengaja mengunci nilai `config.platform.php` ke versi `8.3.0`. Tujuannya supaya berkas `composer.lock` yang dihasilkan selalu resolve ke versi paket-paket dependensi yang benar-benar kompatibel dengan versi PHP minimum yang didukung sistem ini (yaitu `^8.3`), terlepas dari versi PHP apa pun yang kebetulan terpasang di komputer developer yang menjalankan perintah `composer update`. Tanpa pengunci ini, ada risiko composer memilih versi paket yang sebenarnya membutuhkan versi PHP lebih baru daripada yang dijanjikan sebagai syarat minimum sistem.

## Ke Mana Selanjutnya: Dokumentasi Lengkap

Dokumen yang baru saja Anda baca ini hanyalah ringkasan dari keseluruhan sistem. Untuk memahami setiap aspek secara jauh lebih mendalam, folder [`docs/`](docs/README.md) menyediakan serangkaian dokumen khusus yang membahas setiap topik secara tuntas.

| Dokumen | Apa yang Dibahas di Dalamnya |
|---|---|
| [docs/FITUR.md](docs/FITUR.md) | Rincian lengkap setiap fitur, dikelompokkan berdasarkan peran pengguna |
| [docs/HALAMAN.md](docs/HALAMAN.md) | Referensi lengkap setiap halaman: alamat URL-nya, siapa yang boleh mengaksesnya, dan apa fungsinya |
| [docs/ALUR-BISNIS.md](docs/ALUR-BISNIS.md) | Penjelasan mendalam tentang siklus hidup SPK beserta seluruh aturan bisnis yang melatarbelakanginya |
| [docs/DAFTAR-AKTIVITAS.md](docs/DAFTAR-AKTIVITAS.md) | Katalog lengkap aktivitas dan interaksi antar aktor, disusun sebagai bahan mentah untuk penyusunan diagram aktivitas |
| [docs/DATABASE.md](docs/DATABASE.md) | Skema lengkap setiap tabel beserta aturan foreign key yang mengaturnya |
| [docs/KEAMANAN.md](docs/KEAMANAN.md) | Penjelasan langkah-langkah keamanan yang diterapkan, serta keputusan-keputusan desain terkait akses data |
| [docs/DFD.md](docs/DFD.md) | Diagram Alir Data (Data Flow Diagram) sistem, dari tingkat konteks hingga level yang lebih terperinci |
| [docs/USE-CASE-KEBUTUHAN.md](docs/USE-CASE-KEBUTUHAN.md) | Daftar use case, kebutuhan fungsional, dan kebutuhan non-fungsional sistem |
| [docs/SKENARIO-UAT.md](docs/SKENARIO-UAT.md) | Skenario pengujian penerimaan pengguna (User Acceptance Testing) untuk kedua peran |
| [docs/STRUKTUR-BUKU-PANDUAN.md](docs/STRUKTUR-BUKU-PANDUAN.md) | Rancangan struktur bab dan subbab untuk penyusunan buku panduan pengguna |
