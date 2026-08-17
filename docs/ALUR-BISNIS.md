# Alur Bisnis Sistem MARLIN

## Pengantar

Dokumen ini adalah penjelasan paling mendalam yang tersedia tentang bagaimana Sistem MARLIN benar-benar bekerja di balik layar, khususnya mengenai siklus hidup Surat Perintah Kerja (SPK) dan seluruh aturan bisnis yang mengatur perilakunya. Tujuannya bukan sekadar mendeskripsikan *apa* yang terjadi pada setiap tahap, melainkan menjelaskan *mengapa* sistem dirancang berperilaku demikian. Banyak keputusan yang tampak pada pandangan pertama seperti aturan sembarangan, misalnya "kenapa admin tidak bisa langsung memvalidasi satu rambu begitu laporannya masuk, harus menunggu seluruh SPK diajukan dulu," sebenarnya lahir dari pertimbangan yang matang tentang bagaimana pekerjaan nyata di lapangan berlangsung. Dokumen ini akan membongkar pertimbangan-pertimbangan tersebut satu per satu.

Setiap pernyataan di dalam dokumen ini sudah diverifikasi langsung terhadap kode sumber sistem yang sesungguhnya berjalan, bukan sekadar rencana atau spesifikasi awal yang belum tentu terimplementasi dengan tepat. Ini penting untuk ditegaskan, karena sebuah dokumentasi yang tidak lagi sesuai dengan kenyataan kode justru lebih berbahaya daripada tidak ada dokumentasi sama sekali, ia bisa menyesatkan pembaca yang mempercayainya begitu saja. Jika Anda menemukan sebuah kejanggalan antara apa yang tertulis di sini dengan apa yang Anda amati di dalam sistem, anggaplah kode sebagai kebenaran yang lebih baru, dan silakan laporkan supaya dokumen ini bisa disesuaikan kembali.

Untuk memahami dokumen ini dengan baik, ada baiknya Anda sudah membaca [FITUR.md](FITUR.md) terlebih dahulu untuk mendapatkan gambaran permukaan tentang fitur-fitur yang ada, karena dokumen yang sedang Anda baca ini akan sering menjelaskan alasan di balik perilaku yang sudah disebutkan secara singkat di sana.

---

## Menelusuri Siklus Hidup SPK Secara Menyeluruh

Bayangkan sebuah SPK sebagai sebuah cerita yang punya awal, pertengahan yang penuh percabangan, dan akhir yang jelas. Berikut adalah gambaran lengkap alur cerita tersebut, disajikan dalam bentuk diagram alur sederhana terlebih dahulu, kemudian dijelaskan secara naratif setelahnya.

```
Admin buat SPK (tiap baris rambu pilih pasang baru / perbaikan sendiri-sendiri)
        │
        ▼
Petugas (perwakilan) daftarkan tim → gabung SPK
        │
        ▼
Untuk tiap rambu dalam SPK, perwakilan pilih:
        │
        ├─ Ada kendala? ──► Form Kendala (foto + alasan wajib)
        │                   → rambu_pasang.status = tertunda
        │
        ▼ Tidak ada kendala
   Form Laporan Pengerjaan (foto sesudah, GPS, barang/bahan)
                   → rambu_pasang.status = menunggu_validasi
        │
        ▼
Semua rambu dalam SPK sudah tertunda/menunggu_validasi/selesai
(dan minimal satu yang tertunda/menunggu_validasi, bukan semuanya selesai)?
        │
        ▼ Ya, perwakilan bisa "Ajukan Laporan Akhir"
   spk.laporan_akhir_diajukan_at = sekarang
   → SPK masuk antrean Validasi Pengerjaan admin
        │
        ▼
Admin buka Detail Validasi, per rambu centang terima/tidak
   (rambu yang tertunda/kendala TIDAK BISA dicentang, lihat di bawah)
        │
        ├─ Tidak dicentang → wajib isi catatan penolakan
        │   → laporan_pengerjaan.status = ditolak
        │   → rambu_pasang.status = revisi
        │   → perwakilan mengerjakan ulang KHUSUS rambu ini
        │
        ▼ Dicentang (diterima)
   laporan_pengerjaan.status = diterima
   rambu_pasang.status = selesai
   ├─ pasang_baru → rambu.sudah_terpasang = true
   └─ perbaikan   → rambu.kondisi_terkini = baik
        │
        ▼
spk.laporan_akhir_diajukan_at = null (reset gate)
Semua rambu_pasang sudah selesai/batal?
        │
        ▼ Ya
spk.status = selesai (SPK diarsipkan dari Daftar Surat)
```

Sekarang mari kita telusuri cerita ini selangkah demi selangkah dengan bahasa yang lebih naratif, sehingga setiap keputusan percabangan di dalam diagram di atas benar-benar dipahami konteksnya, bukan sekadar dihafalkan sebagai serangkaian langkah teknis.

Segalanya bermula dari meja seorang admin, yang menyusun sebuah SPK baru. Yang membedakan SPK di sistem ini dari sekadar dokumen surat biasa adalah bagaimana ia menampung banyak baris pekerjaan sekaligus di dalamnya, dan setiap baris tersebut menentukan sendiri apakah ia sebuah pemasangan rambu yang benar-benar baru, atau sebuah perbaikan terhadap rambu yang sudah terdaftar sebelumnya di dalam sistem. Begitu SPK ini selesai disusun dan disimpan, ia langsung berstatus aktif dan siap untuk diambil oleh petugas lapangan mana pun yang tersedia.

Di sinilah petugas mulai berperan. Salah satu di antara mereka mendaftarkan diri sebagai perwakilan tim untuk SPK tersebut, sekaligus mengajak rekan-rekan lain yang akan turut serta mengerjakannya secara fisik. Perlu ditekankan bahwa hanya perwakilan inilah yang memiliki wewenang untuk berinteraksi dengan formulir-formulir pelaporan resmi di dalam sistem, sementara anggota tim lainnya boleh ikut bekerja di lapangan tetapi tidak mengoperasikan sistem pelaporan itu sendiri. Alasan di balik pembatasan ini akan dijelaskan lebih dalam pada bagian [Siapa yang boleh mengirim laporan?](#siapa-yang-boleh-mengirim-laporan) di bawah.

Untuk setiap baris rambu di dalam SPK, perwakilan tim menghadapi satu keputusan yang sama setiap kalinya: apakah pekerjaan ini berjalan mulus, atau ada kendala yang menghalanginya. Jika ternyata muncul kendala, misalnya lokasi yang dituju ternyata sudah ditempati rambu lain, atau kondisi jalan sama sekali tidak memungkinkan, perwakilan mengisi Form Kendala. Formulir ini mewajibkan foto pendukung dan penjelasan alasan, dan begitu terkirim, status rambu tersebut di dalam database berubah menjadi `tertunda`. Sebaliknya, jika pekerjaan berjalan lancar tanpa hambatan apa pun, perwakilan mengisi Form Laporan Pengerjaan, lengkap dengan foto hasil akhir, koordinat GPS, dan daftar barang atau bahan yang digunakan bila relevan, membuat status rambu berubah menjadi `menunggu_validasi`.

Setelah seluruh baris rambu di dalam satu SPK sudah melewati salah satu dari dua jalur di atas (baik menjadi tertunda maupun menunggu validasi), dan syarat tertentu terpenuhi (dijelaskan lebih rinci pada bagian ["Siap diajukan" tetap menghitung rambu yang sudah selesai di putaran sebelumnya](#siap-diajukan-tetap-menghitung-rambu-yang-sudah-selesai-di-putaran-sebelumnya) di bawah), tombol "Ajukan Laporan Akhir" akan muncul dan bisa diklik oleh perwakilan. Menekan tombol ini adalah momen penting: kolom `laporan_akhir_diajukan_at` pada SPK tersebut diisi dengan waktu saat ini, dan inilah yang secara resmi memasukkan SPK tersebut ke dalam antrean validasi milik admin.

Kini giliran admin membuka halaman Detail Validasi untuk SPK tersebut. Untuk setiap rambu, admin memilih apakah hasil kerjanya diterima atau tidak, dengan satu pengecualian penting: rambu yang statusnya tertunda karena adanya kendala tidak pernah bisa "dicentang" sebagai diterima, karena secara logis memang belum ada pekerjaan nyata yang bisa dianggap selesai untuknya. Aturan ini dijelaskan lebih lengkap pada bagian [Rambu yang terkendala tidak pernah bisa "diterima" saat validasi](#rambu-yang-terkendala-tidak-pernah-bisa-diterima-saat-validasi).

Untuk rambu yang tidak dicentang, baik karena memang ditolak maupun karena memang sedang tertunda, admin wajib mengisi catatan penolakan yang menjelaskan apa yang perlu diperbaiki. Status laporan pengerjaannya (kalau ada) berubah menjadi ditolak, dan status rambu itu sendiri kembali menjadi `revisi`, mengembalikannya kepada tim petugas untuk dikerjakan ulang secara khusus, tanpa mengganggu rambu-rambu lain di dalam SPK yang sama yang sudah diterima.

Untuk rambu yang dicentang sebagai diterima, statusnya berubah menjadi `selesai`, dan efek sampingnya bergantung pada jenis pekerjaannya: jika ia sebuah pemasangan baru, kolom `sudah_terpasang` pada rambu itu berubah menjadi benar, menandakan bahwa rambu tersebut kini secara resmi menjadi objek fisik yang nyata ada di dunia. Jika ia sebuah perbaikan, kolom `kondisi_terkini` berubah menjadi baik, menandakan bahwa kerusakan yang sebelumnya dilaporkan sudah teratasi.

Apa pun hasil keputusan admin, begitu proses validasi selesai diproses, gerbang `laporan_akhir_diajukan_at` selalu direset kembali menjadi kosong. Ini penting supaya, apabila masih ada rambu yang berstatus revisi, SPK tersebut secara otomatis keluar dari antrean validasi sampai perwakilan mengerjakan ulang bagian yang direvisi dan mengajukan Laporan Akhir sekali lagi. Dan akhirnya, begitu seluruh rambu dalam satu SPK sudah mencapai status selesai atau dibatalkan, tanpa ada satu pun yang masih menggantung, status SPK itu sendiri secara otomatis berubah menjadi selesai, dan SPK tersebut berpindah dari Daftar Surat aktif menuju arsip Riwayat SPK.

---

## Aturan-Aturan Kunci yang Membentuk Perilaku Sistem

Bagian ini membahas setiap aturan bisnis penting secara satu per satu, lengkap dengan penjelasan mendalam tentang konteks dan alasan di baliknya.

### Jenis Pekerjaan Ditentukan Per Baris Rambu, Bukan Per SPK

Salah satu keputusan desain yang paling fundamental dalam sistem ini adalah bahwa SPK itu sendiri **tidak memiliki** kolom "jenis pekerjaan" pada levelnya sendiri. Sebaliknya, setiap baris `rambu_pasang` yang berada di dalam sebuah SPK memilih sendiri jenis pekerjaannya, apakah **Pemasangan Baru** atau **Perbaikan**, sepenuhnya independen dari baris lain di sekitarnya. Konsekuensinya, satu SPK yang sama boleh saja mencampur kedua jenis pekerjaan tersebut dalam satu surat, misalnya satu baris untuk memasang rambu yang benar-benar baru di sebuah persimpangan, dan baris lain untuk memperbaiki rambu larangan yang sudah rusak di jalan yang berbeda, semuanya tercakup dalam satu dokumen surat perintah kerja yang sama.

Pilihan jenis pekerjaan pada setiap baris ini menentukan bagaimana alur pengisian data untuk baris tersebut saat SPK dibuat maupun diedit. Baris berjenis **Pemasangan Baru** selalu meminta admin memasukkan data rambu yang benar-benar baru secara manual, mulai dari jenis rambunya, lokasi spesifiknya, sampai koordinatnya. Baris berjenis **Perbaikan**, di sisi lain, memberi admin dua pilihan: memilih rambu yang memang sudah terdaftar di dalam sistem lewat kotak pencarian (searchable-select), atau mencatat rambu yang secara fisik sebenarnya sudah ada di lapangan namun belum pernah tercatat secara resmi di dalam sistem sebelumnya.

Admin memang diberi keleluasaan untuk mengubah jenis pekerjaan satu baris kapan saja lewat halaman Edit Surat, termasuk untuk baris yang sudah ada sejak SPK pertama kali dibuat, namun keleluasaan ini **dibatasi secara ketat berdasarkan status baris tersebut saat ini**. Selama status sebuah baris masih berupa `Belum`, `Urgent`, atau `Revisi`, artinya belum pernah ada laporan pengerjaan atau kendala nyata yang tercatat atasnya, admin bebas mengubah apa pun tentang baris tersebut, mulai dari jenis pekerjaannya, rambunya, lokasinya, sampai jumlahnya. Namun begitu status baris itu berubah menjadi `Tertunda` atau `Menunggu Validasi`, yang berarti baris tersebut sedang dalam proses ditinjau atau sedang menunggu giliran ditinjau oleh admin, atau bahkan sudah mencapai status `Selesai` karena sudah divalidasi, halaman Edit Surat berhenti menampilkan formulir yang bisa diubah untuk baris tersebut. Sebagai gantinya, ia menampilkan ringkasan yang bersifat baca-saja (read-only), lengkap dengan penjelasan singkat mengapa baris tersebut tidak lagi bisa diedit.

Pembatasan ini bukan sekadar formalitas antarmuka belaka, melainkan sebuah penjagaan integritas data yang nyata. Bayangkan skenario ini: seorang petugas sudah mengirimkan laporan pengerjaan lengkap dengan foto dan koordinat GPS untuk sebuah rambu yang sedang diperbaiki, membuat statusnya menjadi menunggu validasi. Apabila admin kemudian membuka Edit Surat dan mengubah jenis rambu atau lokasinya begitu saja tanpa penjagaan apa pun, laporan pengerjaan yang sudah terlanjur dikirim petugas itu akan menjadi tidak sinkron dengan data rambu yang sebenarnya sedang ditinjau. Inilah sumber dari bug nyata yang pernah ditemukan dalam pengujian sistem, di mana proses validasi bisa menampilkan pesan "berhasil" padahal sebenarnya tidak ada perubahan apa pun yang benar-benar tersimpan, karena data yang dijadikan acuan validasi sudah berubah sebelum keputusan admin sempat diterapkan. Aturan pengunci status ini adalah jawaban langsung atas persoalan tersebut, dan diterapkan bukan hanya di tampilan, tetapi juga diperiksa ulang langsung dari basis data setiap kali data hendak disimpan, sehingga tidak ada celah bagi permintaan yang dimanipulasi untuk melewati pengecekan ini.

Untuk kebutuhan tampilan yang membutuhkan satu nilai ringkas mewakili jenis pekerjaan seluruh SPK, misalnya lencana (badge) pada kartu di halaman Daftar Surat, sistem menyediakan method `Spk::jenisRingkasan()`. Method ini akan mengembalikan jenis pekerjaan yang sama apabila seluruh baris di dalam SPK tersebut memang sejenis, atau mengembalikan nilai kosong (`null`) apabila baris-barisnya bercampur, yang kemudian ditampilkan sebagai lencana khusus bertuliskan **"Pemasangan & Perbaikan"**. Kata-kata otomatis pada bagian perihal Surat Pengantar dalam bentuk PDF juga mengikuti logika serupa namun dengan sedikit bias: begitu ada minimal satu baris berjenis Pemasangan Baru, kata "pemasangan" akan dipakai, dan hanya SPK yang seluruh barisnya murni Perbaikan yang akan memakai kata "perbaikan" pada perihalnya.

### Siapa yang Boleh Mengirim Laporan?

Bergabung ke dalam tim sebuah SPK, lewat aksi yang di dalam kode disebut `daftarkanTim`, bisa dilakukan oleh siapa saja yang mendaftarkan dirinya sebagai perwakilan. Ketika seseorang mendaftar sebagai perwakilan, aksi itu sekaligus mendaftarkan rekan-rekan setimnya juga, semuanya dalam satu langkah yang sama, bukan satu per satu. Namun, dari seluruh anggota tim yang tercatat, hanya perwakilan (ditandai dengan `dikerjakan_oleh.is_perwakilan = true`) yang memiliki wewenang untuk mengoperasikan tiga hal berikut: mengisi Form Laporan Pengerjaan, mengisi Form Kendala, dan mengajukan Laporan Akhir.

Anggota tim yang bukan perwakilan tetap bisa ikut bekerja secara fisik di lapangan bersama rekan-rekannya, dan tetap bisa melihat detail SPK yang mereka kerjakan, tetapi mereka tidak bisa mengoperasikan formulir-formulir pelaporan resmi di atas. Ini adalah keputusan yang disengaja untuk menciptakan satu titik akuntabilitas yang jelas per SPK, sehingga selalu ada kepastian tentang siapa sebenarnya yang bertanggung jawab melaporkan hasil kerja tim tersebut kepada admin, tanpa risiko kebingungan akibat banyak orang bisa melapor secara terpisah dan berpotensi saling bertentangan.

Perlu dicatat bahwa mendaftarkan diri sebagai perwakilan adalah keputusan yang **tidak bisa dibatalkan** lewat sistem, karena memang tidak ada fitur "keluar dari tim" yang tersedia. Karena sifatnya yang permanen inilah, antarmuka sengaja meminta konfirmasi eksplisit terlebih dahulu sebelum aksi ini benar-benar dieksekusi, memberi kesempatan bagi pengguna untuk membatalkan niatnya apabila ternyata itu bukan keputusan yang benar-benar mereka inginkan. Berbeda halnya dengan anggota biasa yang bukan perwakilan: perwakilan bisa menambahkan mereka belakangan lewat aksi `tambahAnggota`, dan bisa pula menghapus mereka kembali lewat aksi `hapusAnggota` apabila ternyata ada kesalahan input nama, keduanya juga tetap diminta konfirmasi lewat antarmuka sebelum benar-benar dijalankan. Sebagai catatan tambahan, baris keanggotaan perwakilan itu sendiri tidak pernah bisa dihapus lewat aksi `hapusAnggota`, hanya baris anggota biasa yang bisa dihapus lewat jalur ini.

### Mengapa Ada Gerbang "Laporan Akhir" Sebelum Validasi?

Salah satu pertanyaan yang paling sering muncul dari orang yang baru mengenal sistem ini adalah mengapa admin tidak langsung memvalidasi sebuah rambu begitu petugas mengirimkan laporannya. Jawabannya terletak pada bagaimana validasi dirancang untuk berjalan **per-batch, per-SPK**, bukan satu rambu sekaligus setiap kali laporan baru masuk. Validasi baru bisa dilakukan setelah **seluruh** rambu di dalam sebuah SPK sudah ditangani, entah lewat laporan pengerjaan yang sukses maupun lewat laporan kendala. Halaman Validasi Laporan, yang menjadi daftar utama tempat admin bekerja, hanya menampilkan SPK yang kolom `laporan_akhir_diajukan_at`-nya sudah terisi. Dengan begitu, admin tidak perlu bolak-balik membuka halaman validasi setiap kali satu rambu baru selesai dilaporkan, cukup menunggu sampai seluruh SPK benar-benar siap untuk ditinjau sekaligus dalam satu sesi kerja.

Ada satu catatan teknis yang penting untuk diketahui: halaman Detail Validasi itu sendiri sebenarnya secara teknis masih bisa diakses langsung lewat alamat URL-nya walaupun `laporan_akhir_diajukan_at` belum terisi, karena tidak ada pengecekan tambahan pada fungsi `mount()` komponennya yang secara eksplisit menolak akses tersebut. Meski demikian, alur normal penggunaan aplikasi selalu mengarahkan admin lewat halaman daftar yang sudah tersaring dengan benar, sehingga dalam praktiknya ini bukan sebuah celah yang berdampak nyata terhadap keamanan atau integritas data, hanya sebuah detail implementasi yang perlu diketahui.

### Mengapa `laporan_akhir_diajukan_at` Selalu Direset ke Kosong Setiap Kali Admin Memvalidasi?

Setiap kali admin selesai memproses sebuah validasi, apa pun hasil keputusannya untuk masing-masing rambu, kolom `laporan_akhir_diajukan_at` pada SPK tersebut selalu dikembalikan menjadi kosong. Tujuannya adalah supaya apabila ada rambu yang ditolak dan kembali berstatus revisi, SPK tersebut secara otomatis "keluar" dari antrean validasi sampai perwakilan tim benar-benar mengajukan ulang Laporan Akhir setelah menyelesaikan revisi yang diminta. Mekanisme ini mencegah admin secara tidak sengaja memvalidasi ulang rambu yang sama berkali-kali tanpa ada kepastian nyata bahwa perwakilan tim memang sudah memperbaiki apa yang diminta dan siap ditinjau kembali.

### "Siap Diajukan" Tetap Menghitung Rambu yang Sudah Selesai di Putaran Sebelumnya

Syarat agar tombol Ajukan Laporan Akhir muncul dan bisa diklik adalah sebagai berikut: harus ada minimal satu rambu yang berstatus `tertunda` atau `menunggu_validasi` (menandakan memang ada sesuatu yang baru saja mau diajukan), dan pada saat yang bersamaan, tidak boleh ada satu pun rambu yang masih berstatus `belum` atau `revisi` (menandakan semua rambu sudah ditangani, tidak ada yang terlewat).

Yang menarik dan penting untuk dipahami di sini adalah bagaimana rambu yang sudah berstatus `selesai` dari putaran validasi sebelumnya tetap dihitung sebagai "sudah ditangani," bukan diabaikan begitu saja. Ini penting untuk kasus nyata berikut: bayangkan sebuah SPK dengan beberapa rambu, di mana sebagian sudah berstatus selesai karena diterima pada putaran validasi pertama, sementara sebagian lainnya baru saja selesai direvisi setelah sebelumnya ditolak. SPK semacam ini harus tetap bisa diajukan ulang oleh perwakilan tim, bukan terkunci secara permanen hanya karena ada campuran status selesai dan tertunda/menunggu validasi di dalamnya.

### Halaman Validasi Menampilkan Semua Rambu dalam SPK, Bukan Cuma yang Baru Masuk

Halaman Detail Validasi sengaja dirancang untuk menampilkan seluruh rambu yang ada di dalam SPK tersebut, termasuk rambu-rambu yang sudah berstatus selesai dari putaran validasi sebelumnya, bukan hanya rambu-rambu yang statusnya sedang tertunda atau menunggu validasi saat ini. Alasannya sederhana namun penting: admin membutuhkan konteks penuh saat mengambil keputusan, terutama untuk SPK yang sudah melalui satu putaran revisi atau lebih. Tanpa konteks penuh ini, admin bisa saja bingung melihat sebuah SPK muncul kembali di antrean validasi dengan hanya satu rambu yang perlu ditinjau, tanpa memahami bahwa rambu-rambu lainnya sebenarnya sudah selesai sejak putaran sebelumnya.

### Perbaikan Bersifat Per Rambu, Bukan Per SPK

Ketika sebuah rambu ditolak validasinya oleh admin, hanya rambu itu saja yang statusnya kembali menjadi revisi. Rambu-rambu lain di dalam SPK yang sama, yang sudah lebih dulu berstatus selesai, sama sekali tidak ikut terganggu oleh penolakan tersebut. Status SPK secara keseluruhan baru berubah menjadi selesai apabila benar-benar **semua** baris `rambu_pasang` di dalamnya sudah mencapai status selesai atau dibatalkan. Pada momen itulah, kolom `spk.selesai_pada` dicatat untuk yang pertama dan satu-satunya kalinya, nilai ini kemudian dipakai untuk menghitung Durasi Pengerjaan dan Selisih dari Deadline yang ditampilkan pada halaman Detail Surat.

Catatan penolakan yang diisi admin (`catatan_penolakan`) tidak hanya tersimpan diam-diam di dalam basis data dan terkirim lewat notifikasi. Ia juga ditampilkan langsung pada kartu rambu yang bersangkutan di halaman Detail SPK yang dilihat petugas, serta pada formulir Kendala maupun Laporan Pengerjaan untuk rambu tersebut, sehingga siapa pun yang membuka halaman itu bisa langsung memahami apa yang perlu diperbaiki tanpa harus mencari-cari informasinya di tempat lain.

Hal serupa juga berlaku untuk alasan kendala (`kendala.alasan`), yang perlu dibedakan dengan jelas dari catatan penolakan di atas: alasan kendala ini diisi oleh **petugas** sendiri saat mereka melaporkan bahwa pemasangan atau perbaikan tidak bisa dilanjutkan, bukan oleh admin. Alasan ini juga ditampilkan pada halaman Detail SPK, baik versi admin maupun versi petugas, dengan gaya kotak peringatan (warning callout) yang sama seperti yang dipakai di halaman Validasi. Sebelum penambahan ini, alasan kendala hanya bisa dilihat lewat halaman Validasi Pengerjaan atau dengan membuka ulang formulir Kendala-nya secara langsung, sekarang siapa pun yang membuka Detail SPK bisa langsung mengetahui mengapa sebuah rambu berstatus tertunda tanpa perlu mencari-cari ke tempat lain.

### Rambu yang Terkendala Tidak Pernah Bisa "Diterima" Saat Validasi

Pada halaman Detail Validasi, rambu yang berstatus `tertunda`, artinya sedang ada kendala yang menghalanginya, sama sekali tidak bisa dicentang sebagai sesuai atau diterima. Kartu rambu semacam ini sengaja tidak diberi interaksi klik apa pun, dan hanya menampilkan sebuah lencana bertuliskan "Akan dikembalikan untuk direvisi." Alasannya cukup sederhana untuk dipahami: kendala berarti pekerjaan itu memang belum benar-benar selesai dikerjakan, sehingga tidak ada laporan pengerjaan nyata apa pun yang bisa "diterima" sebagai bukti bahwa pekerjaan itu selesai. Rambu yang terkendala selalu diarahkan ke jalur penolakan (berubah menjadi status revisi) begitu admin melanjutkan proses validasi, dan admin **wajib** mengisi catatan penolakan untuknya sama seperti rambu berlaporan yang ditolak, meski catatan ini biasanya lebih berisi instruksi atau arahan untuk mengatasi kendala tersebut, bukan berarti pekerjaan yang sudah dilakukan itu salah.

Penjagaan aturan ini tidak hanya diterapkan di tampilan antarmuka, tetapi juga dijaga secara eksplisit di sisi server lewat method `Admin\Validasi\Show::normalisasiCheckedKendala()`. Begitu admin menekan tombol "Proses Validasi" atau "Konfirmasi & Selesaikan," status "dicentang" untuk rambu mana pun yang sedang berstatus tertunda akan dipaksa menjadi tidak dicentang, apa pun data yang sebenarnya dikirim dari sisi klien. Ini memastikan tidak ada jalan, disengaja maupun tidak, untuk menandai sebuah kendala seolah-olah sudah selesai tanpa adanya laporan pengerjaan yang sungguhan.

### Perpanjangan Deadline Saat Menolak Validasi

Selain lewat halaman Edit Surat, admin juga diberi kemudahan untuk memperpanjang tenggat waktu (`deadline`) sebuah SPK langsung dari dalam Form Penolakan pada halaman Detail Validasi, lewat sebuah kotak centang opsional bertuliskan "Beri kelonggaran, perpanjang deadline SPK ini juga." Fitur ini sengaja dipisahkan dari alur Edit Surat yang terpisah supaya admin tidak perlu meninggalkan konteks "mengapa saya menolak rambu ini" hanya untuk memberikan tambahan waktu yang memang wajar diberikan sebagai konsekuensi dari revisi yang diminta.

Apabila kotak centang ini diaktifkan dan tanggal baru diisi, beberapa hal terjadi sekaligus. Kolom `deadline` **dan** `deadline_asli` pada SPK tersebut sama-sama diperbarui, mengikuti konvensi yang sama seperti yang dipakai halaman Edit Surat dan sistem `PenyesuaianDeadlineSpk`, kemudian tingkat urgensi dihitung ulang berdasarkan tenggat waktu yang baru. Perubahan ini juga tercatat ke dalam Audit Log dengan kode aksi `deadline_diperpanjang`, dan seluruh anggota tim yang tergabung dalam SPK tersebut menerima notifikasi bahwa tenggat waktu pekerjaan mereka telah berubah. Penting untuk dicatat bahwa perpanjangan ini berlaku untuk **seluruh SPK**, bukan hanya untuk rambu yang sedang direvisi, karena kolom `deadline` memang merupakan atribut yang berada di level SPK, bukan atribut per rambu.

Perubahan tenggat waktu ini dan proses penolakan rambunya sendiri dibungkus dalam **satu transaksi database yang sama**. Ini memastikan tidak akan pernah terjadi keadaan di mana tenggat waktu yang sudah berubah "tersangkut" tanpa disertai penolakan rambu yang seharusnya menyertainya benar-benar tercatat dengan baik, atau sebaliknya, sebuah penolakan tercatat tanpa perpanjangan tenggat waktu yang seharusnya diberikan bersamaan dengannya.

### Laporan Pengerjaan Bisa Berlapis, dan Bisa Diedit Ulang Sebelum Laporan Akhir Diajukan

Satu baris `rambu_pasang` bisa memiliki lebih dari satu baris `laporan_pengerjaan` seiring berjalannya waktu. Apabila laporan pertama yang dikirimkan ditolak oleh admin, laporan revisi yang dikirimkan setelahnya akan menjadi sebuah baris yang sepenuhnya **baru** di dalam basis data, bukan menimpa baris lama yang sudah ada. Pendekatan ini memastikan seluruh riwayat penolakan tetap tersimpan lengkap dan bisa ditelusuri kembali kapan saja, tidak ada jejak yang hilang begitu saja karena tertimpa data baru.

Selama sebuah SPK belum mengajukan Laporan Akhir, petugas masih diberi keleluasaan untuk membuka ulang Form Kendala atau Form Laporan Pengerjaan pada rambu yang statusnya `tertunda` atau `menunggu_validasi` dan mengedit isinya kembali. Mereka bahkan bisa menukar jenis laporannya sepenuhnya, misalnya semula mengira ada kendala namun ternyata pekerjaannya sudah bisa diselesaikan, tanpa perlu menunggu admin menolaknya terlebih dahulu sebagai prasyarat untuk melakukan koreksi.

## Temuan Kondisi: Sebuah Jalur yang Independen dari SPK Aktif

Berbeda dengan seluruh alur di atas yang berputar mengelilingi SPK, fitur Temuan Kondisi berdiri sebagai jalur yang sepenuhnya terpisah. Petugas bisa melaporkan sebuah rambu yang mereka temukan dalam kondisi rusak **kapan saja**, tanpa terikat pada SPK aktif yang sedang mereka kerjakan sekali pun, misalnya ketika mereka sedang patroli rutin atau kebetulan melintas dan melihat kerusakan di jalan yang bukan bagian dari tugas mereka saat itu. Fitur ini juga **bisa dilakukan oleh siapa saja** yang sedang login ke dalam sistem, bukan hanya perwakilan tim, berbeda dengan Form Kendala dan Form Laporan Pengerjaan yang eksklusif hanya untuk perwakilan.

Begitu sebuah temuan dilaporkan, serangkaian hal terjadi secara berurutan. Pertama, kolom `rambu.kondisi_terkini` langsung berubah menjadi `rusak` seketika itu juga, supaya pin rambu tersebut di peta segera berubah warna tanpa perlu menunggu admin memproses apa pun terlebih dahulu. Kedua, seluruh admin yang terdaftar di dalam sistem menerima notifikasi, yang juga akan terkirim lewat Telegram apabila akun mereka sudah dihubungkan ke bot. Ketiga, laporan tersebut masuk ke dalam antrean Temuan Lapangan milik admin, dengan status tindak lanjut awal berupa `baru`. Keempat, admin memiliki dua pilihan untuk menindaklanjuti laporan ini: membuat SPK perbaikan baru yang secara langsung merujuk kepada `laporan_kondisi_id` temuan tersebut, yang otomatis mengubah status tindak lanjutnya menjadi `sudah_dibuatkan_spk`, atau menolak temuan itu secara langsung apabila memang belum perlu ditindaklanjuti, yang mengubah statusnya menjadi `ditolak` dan mengirim notifikasi kepada pelapor.

Ada satu detail kenyamanan yang layak disebutkan di sini: foto yang sudah dilampirkan petugas saat melaporkan temuan awal akan **ikut terbawa** ke SPK perbaikan yang dibuat berdasarkan temuan itu. Foto tersebut disalin dan dijadikan `foto_survei` milik `rambu_pasang` yang baru, bukan sekadar merujuk ke path file yang sama, sehingga admin tidak perlu repot mengunggah ulang foto yang sebenarnya sudah tersedia. Meski begitu, admin tetap diberi kebebasan untuk menimpanya dengan foto lain apabila mereka mengunggah foto baru saat mengisi formulir SPK tersebut.

## Bagaimana Urgensi Dihitung Secara Otomatis

Kolom `urgensi` pada sebuah SPK **selalu dihitung secara otomatis** oleh sistem, admin sama sekali tidak bisa mengatur nilainya secara manual. Perhitungannya didasarkan pada sisa hari menuju tenggat waktu, mengikuti aturan berikut.

| Kondisi | Urgensi yang Dihasilkan |
|---|---|
| Ditandai sebagai **Prioritas** | Tinggi, terlepas dari berapa lama sisa waktunya |
| Sisa waktu 2 hari atau kurang | Tinggi |
| Sisa waktu 7 hari atau kurang | Sedang |
| Selebihnya | Rendah |

Yang menarik untuk dipahami adalah bahwa kolom `urgensi` yang tersimpan di dalam basis data itu sendiri hanya diisi ulang pada momen-momen tertentu saja, yaitu ketika SPK pertama kali dibuat, ketika ia diedit, atau ketika tenggat waktunya berubah lewat mekanisme perpanjangan manual maupun otomatis dari `PenyesuaianDeadlineSpk`. Ini berarti nilainya bisa menjadi "basi" seiring berjalannya waktu, misalnya sebuah SPK yang tadinya bernilai Sedang semestinya sudah menjadi Tinggi begitu sisa harinya tinggal dua hari, meskipun tidak ada siapa pun yang mengedit apa pun terhadapnya sejak saat itu.

Untuk mengatasi persoalan ini, seluruh tempat di dalam sistem yang menampilkan urgensi untuk SPK yang masih berstatus **Aktif**, mulai dari lencana pada kartu SPK, pin di peta, sampai pengurutan "butuh perhatian" pada Dashboard Admin, tidak pernah membaca kolom `urgensi` yang tersimpan secara langsung. Sebagai gantinya, mereka semua memanggil method `Spk::urgensiSaatIni()`, yang menghitung ulang nilai urgensi secara langsung (live) dari tenggat waktu dan status prioritas SPK pada saat itu juga. Namun untuk SPK yang sudah berstatus **Selesai** atau **Dibatalkan**, method `urgensiSaatIni()` justru mengembalikan nilai `urgensi` yang tersimpan apa adanya, dibekukan sebagaimana adanya, karena menghitung ulang urgensi untuk pekerjaan yang sudah final sama sekali tidak ada gunanya lagi.

## Warna Pin di Peta: Urutan Prioritas Penentuannya

Warna sebuah pin di peta dihitung sepenuhnya di sisi klien (JavaScript), **bukan** merupakan kolom yang tersimpan di dalam basis data. Pendekatan ini dipilih supaya tampilan peta selalu mencerminkan keadaan terkini secara real-time, tanpa perlu melakukan kueri tambahan ke server setiap kali warna perlu dihitung ulang. Urutan pengecekan berikut menentukan warna mana yang dipakai, dan aturan yang pertama kali cocok itulah yang dipakai, mengabaikan aturan-aturan setelahnya.

Warna **cyan** dipakai apabila `rambu_pasang.status` bernilai `menunggu_validasi`. Warna ini menang atas segala aturan lainnya, bahkan mengalahkan status prioritas atau urgent sekali pun, karena laporan pengerjaannya sudah dikirim dan kini giliran admin untuk bertindak, sehingga tidak perlu lagi terlihat "darurat" di peta selama menunggu proses itu.

Warna **merah** dipakai apabila statusnya `urgent`, atau apabila SPK yang menaunginya ditandai `prioritas = true`, atau apabila `urgensi` SPK tersebut bernilai tinggi.

Warna **kuning** dipakai apabila `kondisi_terkini` rambu tersebut bernilai `rusak`, atau apabila rambu itu sedang dalam proses `perbaikan` yang belum mencapai status selesai.

Warna **biru tua** dipakai apabila statusnya `selesai` (atau tidak ada tugas aktif sama sekali) **dan** kondisi terkininya `baik`.

Warna **abu-abu** dipakai untuk semua situasi selain yang disebutkan di atas, menandakan rambu yang belum mulai dikerjakan.

Perlu dicatat bahwa urutan prioritas ini **sengaja berbeda** dari draft spesifikasi awal proyek, yang semula menyebutkan bahwa warna merah harus selalu menang atas seluruh warna lainnya tanpa terkecuali. Keputusan mengubahnya diambil lewat diskusi tim, dengan alasan bahwa status "menunggu validasi" harus tetap terlihat perkembangannya di peta, bukan tertutup begitu saja oleh warna merah selama sebuah rambu sedang menunggu giliran ditinjau oleh admin.

Peta itu sendiri menggunakan tile OpenStreetMap standar sebagai latar belakangnya. Widget peta ringkas yang tersedia di Dashboard Admin dilengkapi dengan tombol **Unduh PDF**, yang mengambil cuplikan gambar dari peta yang sedang ditampilkan (lewat pustaka `leaflet-image`, dengan ikon pin yang disintesis ulang menjadi gambar tersendiri karena pin aslinya berupa elemen HTML biasa, bukan berkas gambar) dan menyertakannya ke dalam laporan PDF sebaran rambu yang dihasilkan.

## Dua Konsep Foto yang Berbeda: "Terkini" versus "Beku Sejak Dibuat"

Sistem ini sengaja membedakan dua konsep foto yang mudah tertukar apabila tidak dijelaskan dengan hati-hati, dan penting untuk tidak menyamakan keduanya begitu saja.

Foto rambu yang bersifat **"terkini"**, yang ditampilkan pada kartu info pin di halaman Peta dan pada halaman Detail Rambu, dihitung lewat method `Rambu::fotoUtama()`. Method ini bekerja dengan logika berikut: untuk baris `rambu_pasang` yang paling baru milik rambu tersebut, foto sesudah (`laporan_pengerjaan.foto_sesudah`) dari laporan pengerjaan yang paling baru selalu diprioritaskan di atas foto survei (`foto_survei`) milik `rambu_pasang` itu sendiri. Ini masuk akal karena laporan pengerjaan memang selalu dibuat belakangan setelah survei awal, sehingga lebih mencerminkan kondisi rambu yang sebenarnya saat ini. Apabila belum ada laporan pengerjaan sama sekali, sistem akan mundur ke foto survei sebagai gantinya, dan apabila `rambu_pasang` itu sendiri juga tidak memiliki foto survei, sistem akan mencoba `rambu_pasang` yang lebih lama lagi. Konsekuensinya, begitu sebuah rambu selesai dikerjakan dan divalidasi, foto yang ditampilkan pada halaman Peta maupun Detail Rambu secara otomatis berganti dari foto "sebelum" menjadi foto "sesudah," tanpa perlu ada aksi tambahan apa pun dari siapa pun.

Sebaliknya, foto yang berkaitan dengan SPK dan Detail SPK, yang muncul pada kartu daftar SPK, bagian "Daftar Rambu" pada Detail SPK, maupun dokumen Surat Pengantar dalam bentuk PDF, **selalu bersifat beku**. Foto-foto ini hanya memakai `rambu_pasang.foto_survei` apa adanya, dan tidak pernah ikut berubah walaupun rambu tersebut sudah dikerjakan dan foto sesudahnya sudah tersedia. Ini adalah keputusan yang disengaja: dokumen dan kartu yang terkait dengan satu SPK memang harus tetap menggambarkan kondisi "sejak SPK ini dibuat" sebagai catatan historis yang tetap, bukan kondisi rambu yang terus berubah seiring berjalannya waktu.

## Pembatalan SPK

Admin diberi wewenang untuk membatalkan seluruh SPK yang masih berstatus **aktif**, lewat tombol Batalkan SPK yang tersedia di halaman Detail Surat, dan seperti kebanyakan aksi yang berdampak besar lainnya di dalam sistem ini, aksi ini meminta konfirmasi eksplisit terlebih dahulu sebelum benar-benar dieksekusi. Berikut adalah efek-efek yang terjadi begitu pembatalan ini dilakukan.

Pertama, kolom `spk.status` berubah menjadi `dibatalkan`. Sekali lagi ditekankan di sini, **baris SPK tidak pernah dihapus (`DELETE`)** dari basis data, hanya statusnya yang berubah. Kedua, seluruh baris `rambu_pasang` yang belum mencapai status selesai atau dibatalkan sebelumnya, ditandai menjadi `batal`. Ketiga, rambu berjenis pemasangan baru yang belum pernah benar-benar terpasang secara fisik (kolom `sudah_terpasang` masih bernilai salah) otomatis menghilang dari tampilan peta, karena rambu semacam ini memang belum pernah menjadi objek fisik yang nyata ada di dunia. Keempat, rambu berjenis perbaikan tetap ditampilkan di peta apa adanya, dengan `kondisi_terkini` yang tetap tercatat rusak, menunggu SPK perbaikan lain di masa depan untuk menanganinya. Kelima, seluruh anggota tim yang sudah bergabung ke SPK tersebut menerima notifikasi bahwa pekerjaan yang mereka kerjakan telah dibatalkan.

## Pembatalan Satu Rambu (Berbeda dengan Pembatalan Seluruh SPK)

Dari halaman Edit Surat, admin juga bisa membatalkan hanya **satu baris rambu saja** tanpa perlu membatalkan seluruh SPK, misalnya apabila ternyata di lokasi yang dituju sudah ada rambu lain, atau rambu itu memang tidak jadi dikerjakan karena alasan tertentu. Sama seperti aturan pengeditan yang sudah dijelaskan sebelumnya, kemampuan membatalkan satu baris rambu ini **hanya tersedia selama baris tersebut masih berstatus `Belum`, `Urgent`, atau `Revisi`**, mengikuti syarat editabilitas yang sama.

Ada beberapa perbedaan penting antara pembatalan satu rambu dengan pembatalan seluruh SPK. Pertama, pembatalan satu rambu mewajibkan admin mengisi `catatan_pembatalan` yang berisi alasan, lewat sebuah modal konfirmasi, dan catatan ini tersimpan langsung pada baris `rambu_pasang` yang bersangkutan. Kedua, rambu-rambu lain di dalam SPK yang sama, maupun status SPK itu sendiri, sama sekali tidak terpengaruh oleh pembatalan ini. Ketiga, alasan pembatalannya ditampilkan pada kartu rambu di halaman Detail Surat, pada kolom Info di dalam dokumen Surat Pengantar berbentuk PDF, dan pada kolom Keterangan di dalam Laporan Rambu, sehingga siapa pun yang membaca dokumen-dokumen tersebut bisa mengetahui alasan mengapa rambu itu tidak jadi dikerjakan.

Sebagai alternatif dari pembatalan, apabila sebuah rambu memang murni salah input, dengan status yang masih `belum` atau `batal` dan belum pernah ada kendala atau laporan pengerjaan sama sekali yang tercatat atasnya, admin bisa **menghapus permanen** baris `rambu_pasang`-nya, bukan sekadar membatalkannya. Baris `rambu` fisiknya sendiri tidak ikut terhapus dalam proses ini, ia tetap tersimpan sebagai aset, hanya saja sudah tidak lagi terkait dengan SPK tersebut.

## Filter Rentang Tanggal yang Konsisten di Berbagai Halaman Riwayat

Beberapa halaman yang menampilkan data historis, seperti Riwayat SPK milik admin, Riwayat Pekerjaan Saya milik petugas, dan Riwayat Aktivitas (Audit Log), sama-sama menggunakan pola filter rentang tanggal berupa "Dari Tanggal" dan "Sampai Tanggal", menggantikan pendekatan filter per bulan tunggal yang sebelumnya dipakai. Ketika kedua kolom filter ini dibiarkan kosong, halaman-halaman tersebut menampilkan **seluruh riwayat** yang ada, bukan dibatasi ke periode waktu tertentu secara diam-diam. Ini adalah keputusan yang disengaja: seorang pengguna yang membuka halaman riwayat untuk pertama kalinya lebih sering ingin melihat gambaran menyeluruh terlebih dahulu, baru kemudian mempersempitnya dengan filter apabila memang dibutuhkan, ketimbang dihadapkan dengan daftar kosong hanya karena filter bulan berjalan secara diam-diam sudah aktif tanpa mereka sadari.

Untuk Riwayat SPK milik admin, rentang tanggal ini didasarkan pada kapan sebuah SPK terakhir diperbarui (`updated_at`), yang secara praktis mencerminkan kapan SPK tersebut selesai atau dibatalkan, karena kedua peristiwa itulah yang biasanya menjadi perubahan terakhir sebuah SPK sebelum ia berhenti aktif. Untuk Riwayat Pekerjaan Saya milik petugas, rentang tanggal ini didasarkan pada kapan petugas tersebut bergabung ke sebuah SPK (`dikerjakan_oleh.created_at`), sebuah proksi yang paling jujur untuk menjawab pertanyaan "pekerjaan apa saja yang sedang saya kerjakan pada periode ini," terlepas dari kapan SPK itu sendiri akhirnya selesai. Dan untuk Riwayat Aktivitas, rentang tanggal ini didasarkan pada kapan sebuah baris log tercatat (`created_at`), yang juga dilengkapi dengan filter tambahan berupa jenis aksi dan, khusus untuk admin, filter berdasarkan pengguna tertentu.
