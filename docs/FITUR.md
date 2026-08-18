# Fitur Sistem MARLIN

## Pengantar

Dokumen ini merinci setiap fitur yang tersedia di dalam Sistem MARLIN, dikelompokkan berdasarkan peran pengguna yang menggunakannya. Tujuan dokumen ini adalah menjawab pertanyaan "apa saja yang bisa dilakukan pengguna di sini, dan bagaimana persisnya perilaku setiap fitur tersebut," dengan tingkat kerincian yang cukup untuk dijadikan acuan pengujian maupun pengembangan lebih lanjut.

Untuk gambaran umum tentang sistem secara keseluruhan, silakan baca [README.md](../README.md) di direktori akar proyek terlebih dahulu. Untuk mengetahui alamat URL setiap halaman yang menaungi fitur-fitur di bawah ini beserta aturan aksesnya, lihat [HALAMAN.md](HALAMAN.md). Dan untuk memahami alasan mendalam di balik aturan-aturan bisnis yang disebutkan secara singkat di sini, silakan telusuri [ALUR-BISNIS.md](ALUR-BISNIS.md), yang membahas setiap keputusan desain secara jauh lebih terperinci.

---

## Daftar Isi

- [Fitur Admin](#fitur-admin)
- [Fitur Petugas Lapangan](#fitur-petugas-lapangan)
- [Fitur Bersama (Admin & Petugas)](#fitur-bersama-admin--petugas)

---

## Fitur Admin

### Dashboard Admin

Halaman ini adalah titik masuk utama seorang admin ke dalam sistem, disajikan segera setelah mereka berhasil login. Ia menyajikan ringkasan angka-angka yang paling penting untuk diketahui dalam sekali pandang: jumlah SPK yang sedang berstatus aktif, jumlah rambu yang sedang dalam kondisi rusak, dan jumlah laporan pengerjaan yang sedang menunggu validasi dari admin.

Selain ringkasan angka tersebut, halaman ini juga menyematkan sebuah widget peta ringkas dengan filternya sendiri, terpisah dari filter yang ada di halaman Peta penuh. Filter pada widget ini mencakup jenis rambu, tingkat urgensi, dan rentang tanggal. Widget ini juga dilengkapi dengan tombol **Unduh PDF**, yang menghasilkan sebuah laporan sebaran rambu lengkap, berisi cuplikan gambar peta (mencakup tile latar belakang beserta pin-pin rambu yang ditampilkan) sesuai dengan filter yang sedang aktif pada saat tombol itu ditekan, dilengkapi dengan tabel analitik dan daftar rambu yang relevan.

### Buat Surat (SPK)

Halaman ini adalah tempat admin menyusun sebuah Surat Perintah Kerja yang baru, dan merupakan salah satu formulir paling kaya di seluruh sistem. Berikut adalah rincian setiap aspek dari formulir ini.

Setiap baris rambu di dalam surat yang sedang disusun memiliki jenis pekerjaannya sendiri, yaitu **Pemasangan Baru** atau **Perbaikan**. Karena pilihan ini bersifat independen per baris, satu surat yang sama bisa mencampur keduanya sekaligus, misalnya satu baris untuk memasang rambu yang benar-benar baru, dan baris lain untuk memperbaiki rambu yang sudah terdaftar sebelumnya, tanpa lagi terikat pada aturan lama yang mengharuskan seluruh surat memakai satu jenis pekerjaan yang sama untuk semua barisnya.

Alamat lokasi dipecah menjadi field-field yang terstruktur, yaitu **Jalan**, **RT**, dan **Kelurahan**. Kolom `wilayah`, yang merupakan teks gabungan seperti "Jl. X RT. Y Kel. Z", disusun secara otomatis dari field-field yang benar-benar terisi, dan dipakai di dalam Surat Pengantar maupun berbagai tampilan lain tanpa admin perlu mengetiknya secara manual sebagai teks bebas. Hanya **Jalan** yang wajib diisi. **RT** dan **Kelurahan** bersifat opsional dan boleh dikosongkan, karena tidak setiap lokasi berada di dalam sebuah RT atau kelurahan yang jelas, misalnya sebuah titik di sepanjang jalan tol atau penanda kilometer pada jalan raya, di mana batas administratif semacam itu memang tidak berlaku atau tidak diketahui. Apabila dikosongkan, kolom `wilayah` yang tersusun otomatis cukup memuat bagian yang memang terisi, misalnya hanya "Jl. A. Yani KM 12" tanpa embel-embel RT atau Kelurahan sama sekali. Khusus field **RT**, apabila diisi, hanya angka yang diperbolehkan sebagai isiannya.

Admin bisa menambahkan banyak baris rambu sekaligus ke dalam satu surat, lewat tombol **Tambah Rambu** yang berada di bagian bawah daftar rambu yang sedang disusun. Untuk baris yang berjenis **Perbaikan**, admin diberi dua pilihan: memilih rambu yang memang sudah terdaftar di dalam sistem lewat kotak pencarian (searchable-select), atau mencatat rambu yang secara fisik sebenarnya sudah ada di lapangan namun belum pernah tercatat secara resmi di dalam sistem sebelumnya.

Apabila SPK ini dibuat lewat tombol "Buat SPK" yang tersedia pada halaman Temuan Lapangan, foto yang sudah dilampirkan petugas saat melaporkan temuan tersebut akan otomatis dipakai sebagai foto rambu di dalam SPK baru ini, sehingga admin tidak perlu mengunggah ulang foto yang sebenarnya sudah tersedia, meski tetap bisa menggantinya dengan foto baru apabila diperlukan.

Field **Perihal Permohonan** bersifat opsional. Apabila dikosongkan, sistem akan menyusunnya secara otomatis berdasarkan jenis pekerjaan dan jenis rambu yang bersangkutan, misalnya menjadi "pemasangan cermin tikungan".

Field **Tanggal Survei** juga bersifat opsional, namun apabila diisi, ia akan muncul di dalam Surat Pengantar dengan format "(DISURVEI TGL ...)". Field ini tidak boleh diisi dengan tanggal di masa depan, karena survei secara logis mencatat sesuatu yang memang sudah terjadi.

Field **Petugas Survei** juga bersifat opsional, namun wajib diisi apabila Tanggal Survei diisi. Field ini mencatat nama-nama petugas yang melakukan survei secara manual sebagai teks bebas, bukan dipilih dari daftar akun terdaftar, karena tidak selalu petugas sistem yang benar-benar turun melakukan survei. Field ini tidak ditampilkan di dalam Surat Pengantar, hanya muncul pada halaman Detail Surat, dan hanya boleh diisi dengan huruf, spasi, dan koma sebagai pemisah antar nama.

Data **Contact Person**, mencakup nama dan nomor telepon, merepresentasikan kontak lokal yang bisa dihubungi petugas di lapangan, bukan identitas resmi RT atau perwakilan warga yang akan menandatangani surat. Nama RT atau perwakilan yang sebenarnya justru ditulis tangan secara langsung saat kunjungan lapangan berlangsung, tidak diketik di dalam sistem. Field nama hanya boleh diisi huruf dan spasi, sementara field telepon hanya boleh diisi angka.

Field **Deadline** wajib diisi dengan tanggal yang berada setelah hari ini, tidak boleh diisi dengan hari ini sendiri atau tanggal yang sudah lewat. Tingkat **Urgensi** dihitung secara otomatis: sisa dua hari atau kurang menuju tenggat waktu, atau ditandai sebagai **Prioritas**, akan menghasilkan urgensi Tinggi; sisa tujuh hari atau kurang menghasilkan urgensi Sedang; dan selebihnya menghasilkan urgensi Rendah.

Terakhir, admin bisa mengunggah **File Referensi**, yaitu hasil pindaian surat permohonan asli, dengan ukuran maksimal 5 MB.

### Edit Surat (SPK)

Halaman ini hanya tersedia selama status SPK yang bersangkutan masih **Aktif**. Mencoba mengakses halaman ini untuk SPK yang sudah berstatus Selesai atau Dibatalkan akan langsung ditolak oleh sistem dengan kode kesalahan 403.

Selama SPK masih aktif, admin bisa mengubah data header-nya secara bebas: Jalan/RT/Kelurahan, Perihal, Deadline, status Prioritas, Asal Permintaan, Keterangan Asal, Tanggal Survei, Petugas Survei, File Referensi, Catatan, dan data Contact Person.

Untuk daftar rambunya sendiri, ada satu aturan penting yang perlu dipahami dengan seksama: kemampuan admin untuk mengubah sebuah baris rambu **bergantung sepenuhnya pada status baris tersebut saat ini**. Selama status sebuah baris masih `Belum`, `Urgent`, atau `Revisi`, admin bisa mengedit field-field rambunya secara bebas, mengganti ke rambu terdaftar lain (khusus untuk baris berjenis Perbaikan), mengubah jenis pekerjaan baris tersebut, membatalkannya secara individual dengan mengisi alasan (rambu lain di SPK yang sama sama sekali tidak ikut terganggu), atau bahkan menghapusnya secara permanen apabila baris itu memang murni salah input, dengan syarat statusnya masih `Belum` atau `Batal` dan belum ada kendala maupun laporan apa pun yang tercatat atasnya.

Namun, begitu status sebuah baris rambu berubah menjadi `Tertunda` atau `Menunggu Validasi`, yang berarti sudah ada kendala atau laporan pengerjaan nyata yang tercatat atasnya, atau bahkan sudah mencapai status `Selesai` karena sudah divalidasi admin, baris tersebut **terkunci dari perubahan lebih lanjut**. Halaman Edit Surat akan menampilkan baris semacam ini sebagai sebuah ringkasan yang bersifat baca-saja, lengkap dengan penjelasan singkat mengapa baris itu tidak lagi bisa diedit, dan tombol Batalkan pun tidak lagi muncul untuknya. Penguncian ini diterapkan secara ketat, bukan hanya di sisi tampilan antarmuka, tetapi juga diperiksa ulang langsung terhadap data yang tersimpan di basis data setiap kali admin mencoba menyimpan perubahan, sehingga tidak ada celah bagi permintaan yang dimanipulasi untuk melewati penguncian ini. Alasan mendalam di balik aturan ini, termasuk bug nyata yang pernah ditemukan sebagai akibat dari ketiadaan aturan ini, dijelaskan pada bagian [Jenis Pekerjaan Ditentukan Per Baris Rambu, Bukan Per SPK](ALUR-BISNIS.md#jenis-pekerjaan-ditentukan-per-baris-rambu-bukan-per-spk) di dalam ALUR-BISNIS.md.

Admin tetap bisa menambahkan baris rambu yang benar-benar baru ke dalam SPK yang sedang berjalan, terlepas dari status baris-baris lain yang sudah ada. Setiap perubahan yang dilakukan lewat halaman ini tercatat ke dalam Audit Log dengan kode aksi seperti `spk_diedit`, `rambu_pasang_dibatalkan`, dan `rambu_pasang_dihapus`.

### Batalkan SPK

Tombol untuk membatalkan seluruh SPK hanya muncul pada halaman Detail Surat selama status SPK masih **Aktif**, dan sebuah modal konfirmasi selalu ditampilkan terlebih dahulu sebelum aksi ini benar-benar dieksekusi. SPK **tidak pernah dihapus secara permanen** dari sistem, statusnya hanya diubah menjadi `dibatalkan`. Seluruh baris `rambu_pasang` yang belum mencapai status selesai ikut ditandai `batal` secara otomatis. Rambu berjenis pemasangan baru yang belum pernah benar-benar terpasang secara fisik akan otomatis menghilang dari tampilan peta, karena ia memang belum pernah menjadi objek nyata; sementara rambu berjenis perbaikan tetap ditampilkan di peta apa adanya, menunggu SPK perbaikan lain di masa depan. Aksi ini tercatat ke dalam Audit Log dengan kode aksi `spk_dibatalkan`.

### Daftar Surat

Halaman ini menyediakan pencarian berdasarkan nomor surat atau wilayah, serta filter berdasarkan jenis pekerjaan, yang dianggap cocok apabila SPK tersebut memiliki *minimal satu* baris rambu dari jenis yang sedang difilter, sehingga sebuah SPK yang bersifat campuran akan tetap muncul pada kedua filter jenis tersebut sekaligus.

Halaman ini **hanya menampilkan SPK berstatus Aktif**. SPK yang sudah Selesai atau Dibatalkan berpindah ke halaman Riwayat SPK yang terpisah, menjaga daftar utama ini tetap fokus pada pekerjaan yang memang masih berjalan.

Setiap kartu SPK menampilkan foto: apabila lebih dari satu rambu di dalam SPK tersebut memiliki foto, kartunya akan secara otomatis bersilih-ganti (auto-cycle) melalui foto-foto tersebut dengan transisi memudar (crossfade) setiap empat detik, lewat komponen `<x-photo-slideshow>`. Sebaliknya, apabila hanya ada satu foto atau bahkan tidak ada foto sama sekali, kartu itu cukup menampilkannya secara statis atau menampilkan gambar placeholder, tanpa timer yang tidak diperlukan.

Kartu juga diberi lingkaran berwarna (ring) dan lencana berupa "Tim Terdaftar" atau "Belum Ada Tim", tergantung ada tidaknya tim yang sudah bergabung mengerjakan SPK tersebut, memudahkan admin memantau SPK aktif mana saja yang belum diambil oleh siapa pun. Lencana jenis pekerjaan pada kartu menampilkan jenisnya (Pemasangan Baru atau Perbaikan) apabila seluruh baris rambu di dalam SPK tersebut sejenis, atau menampilkan lencana "Pemasangan & Perbaikan" apabila baris-barisnya bercampur, mengikuti hasil dari method `Spk::jenisRingkasan()`.

### Riwayat SPK

Halaman ini menjadi arsip untuk SPK yang sudah **Selesai** atau **Dibatalkan**, sengaja dipisah dari Daftar Surat supaya arsip pekerjaan lama tidak bercampur dengan pekerjaan yang masih aktif berjalan.

Filter yang tersedia di halaman ini mencakup pencarian berdasarkan nomor surat atau wilayah, filter status (Selesai atau Dibatalkan), filter jenis pekerjaan (termasuk opsi "Pemasangan & Perbaikan" untuk SPK yang bersifat campuran), dan filter rentang tanggal berupa "Dari Tanggal" sampai "Sampai Tanggal", yang didasarkan pada kapan SPK tersebut terakhir diperbarui, secara praktis mencerminkan kapan ia selesai atau dibatalkan. Ketika kedua kolom tanggal ini dibiarkan kosong, seluruh riwayat akan ditampilkan tanpa batasan periode apa pun.

Kartu SPK pada halaman ini memakai slideshow foto yang sama seperti pada Daftar Surat. Menekan tombol "Lihat Detail" tetap membuka halaman Detail Surat yang sama persis seperti untuk SPK aktif, hanya saja tombol Edit dan Batalkan tidak lagi muncul karena SPK-nya sudah mencapai keadaan final.

Khusus untuk SPK yang berstatus **Selesai**, halaman Detail Surat menampilkan **Durasi Pengerjaan**, yaitu jumlah hari dari SPK dibuat sampai selesai, dan **Selisih dari Deadline**, ditampilkan sebagai lencana berwarna hijau apabila selesai lebih cepat dari tenggat waktu, atau merah apabila terlambat. Data ini juga tersedia lewat method-method khusus pada model `Spk`, sehingga bisa dimanfaatkan untuk kebutuhan analitik dashboard atau laporan di masa mendatang. Rambu yang dibatalkan satu per satu lewat halaman Edit Surat menampilkan alasan pembatalannya langsung pada kartu rambu yang bersangkutan, dan alasan ini juga muncul pada Surat Pengantar serta Laporan Rambu.

### Validasi Pengerjaan

Halaman ini menampilkan daftar SPK yang sudah mengajukan **Laporan Akhir**, penjelasan lengkap tentang gerbang ini tersedia pada [ALUR-BISNIS.md](ALUR-BISNIS.md). Halaman detailnya menampilkan **semua** rambu di dalam SPK yang bersangkutan, termasuk yang sudah divalidasi pada putaran sebelumnya, supaya admin bisa melihat konteks penuh, bukan hanya rambu-rambu yang baru saja masuk kembali setelah revisi.

Untuk setiap rambu, admin memilih apakah hasil kerjanya sesuai atau tidak, lewat sebuah kartu yang bisa diklik di mana saja, bukan sekadar sebuah kotak centang kecil di pojok. Rambu yang sedang terkendala dan rambu yang sudah memiliki laporan pengerjaan ditampilkan dengan gaya kartu yang sama, namun **hanya kartu laporan pengerjaan yang bisa diklik dan dicentang**. Kartu kendala sama sekali tidak memiliki interaksi klik apa pun, dan tetap menampilkan lencana "Akan dikembalikan untuk direvisi" secara permanen, karena kendala memang berarti pekerjaan itu belum selesai, sehingga tidak ada yang bisa "diterima" darinya. Sistem juga menolak permintaan yang mencoba memaksa kendala tercentang lewat cara apa pun di luar antarmuka normal, penjagaan ini diterapkan langsung di sisi server.

Rambu yang tidak dicentang, termasuk seluruh rambu yang terkendala secara otomatis, wajib diisi dengan **catatan penolakan** terlebih dahulu. Statusnya kemudian kembali menjadi `revisi`, dan petugas mengerjakan ulang khusus rambu tersebut, tanpa mengganggu rambu lain di dalam SPK yang sama. Alasan ini juga ditampilkan kembali kepada petugas pada halaman Detail SPK dan formulir Kendala atau Laporan Pengerjaan mereka, sehingga jelas apa yang perlu diperbaiki.

Di dalam formulir penolakan yang sama, admin bisa opsional mencentang kotak **"Beri kelonggaran, perpanjang deadline SPK ini juga"** untuk langsung menggeser tenggat waktu (berlaku untuk seluruh SPK, bukan hanya rambu yang sedang direvisi) tanpa perlu membuka halaman Edit Surat secara terpisah. Perubahan tenggat waktu ini, penolakan rambunya, penghitungan ulang urgensi, pencatatan ke Audit Log, dan pengiriman notifikasi kepada seluruh tim, semuanya digabungkan menjadi satu transaksi tunggal, sebagaimana dijelaskan lebih dalam pada [ALUR-BISNIS.md](ALUR-BISNIS.md).

Rambu yang diterima akan mengalami perubahan tambahan sesuai jenis pekerjaannya: rambu berjenis `pasang_baru` membuat `rambu.sudah_terpasang` menjadi benar, sementara rambu berjenis `perbaikan` membuat `rambu.kondisi_terkini` menjadi baik. Apabila seluruh rambu di dalam SPK sudah mencapai status `selesai` atau `batal`, status SPK secara otomatis berubah menjadi `selesai`, dan sistem mencatat `selesai_pada` untuk dipakai sebagai bahan analitik durasi pengerjaan.

Sebagai catatan tambahan tentang keandalan, sistem juga dirancang untuk menghindari situasi di mana proses validasi terlihat seolah-olah berhasil padahal sebenarnya tidak ada perubahan nyata yang tersimpan, misalnya apabila sebuah rambu ternyata sudah berubah status di sesi lain sebelum keputusan admin sempat diterapkan. Dalam situasi semacam itu, sistem menampilkan pesan kesalahan yang jelas dan memuat ulang daftar rambu yang sebenarnya masih tersisa untuk diproses, alih-alih diam-diam menampilkan pesan sukses yang menyesatkan.

### Laporan Bulanan

Halaman ini menyajikan rekap jumlah rambu terpasang atau belum terpasang, serta SPK yang selesai atau belum, dengan filter yang cukup fleksibel: rentang tanggal bebas, jenis rambu, dan status rambu (mengikuti enum `StatusRambuPasang`) khusus untuk tabel Detail Rambu. Rekap ini bisa diekspor menjadi berkas PDF untuk dilaporkan kepada atasan.

### Laporan Rambu

Halaman ini menampilkan daftar riwayat pekerjaan per rambu (berbasis baris `rambu_pasang`), dengan filter yang sama fleksibelnya seperti Laporan Bulanan: rentang tanggal bebas, jenis rambu, dan status. Sama seperti Laporan Bulanan, halaman ini juga bisa diekspor menjadi PDF, dan yang penting untuk diketahui, keduanya berbagi query filter yang sama persis lewat kelas `App\Support\LaporanRambu`, memastikan angka yang ditampilkan pada kedua laporan tersebut selalu konsisten satu sama lain.

### Kelola Jenis Rambu

Halaman ini mengelola data master kategori rambu: nama, spesifikasi standar, gambar referensi, dan bentuk ikon yang ditampilkan di peta (bulat atau kotak). Field nama jenis hanya boleh diisi huruf dan spasi. Menghapus sebuah jenis rambu dilakukan lewat sebuah modal konfirmasi bergaya khas aplikasi ini, bukan lagi lewat dialog konfirmasi bawaan peramban, sehingga pengalamannya tetap konsisten dengan seluruh aksi hapus/konfirmasi lain di dalam sistem.

### Kelola Rambu

Halaman ini menampilkan daftar seluruh rambu yang terpasang di dalam sistem, dengan filter berdasarkan kondisi atau wilayah. Rambu yang belum terpasang (kolom `sudah_terpasang` masih bernilai salah) menampilkan kondisinya sebagai **N/A**, bukan "Baik", karena rambu yang belum benar-benar ada secara fisik memang tidak memiliki kondisi nyata apa pun untuk dilaporkan.

### Kelola Pengguna

Halaman ini digunakan untuk menambah atau mengedit akun petugas maupun admin. Akun bisa **diaktifkan atau dinonaktifkan**, bukan dihapus secara permanen. Akun yang dinonaktifkan tidak bisa login lagi (lihat [KEAMANAN.md](KEAMANAN.md) untuk penjelasan lebih lanjut), meski sesi yang kebetulan sudah berjalan sebelumnya tidak akan otomatis terputus begitu saja. Field **Nama** dan **Nama Panggilan** hanya boleh diisi huruf dan spasi, sementara field **No. Telepon** hanya boleh diisi angka.

### Temuan Kondisi Belum Ditindaklanjuti

Halaman ini menampilkan antrean laporan kerusakan dari petugas (tersimpan pada tabel `laporan_kondisi`) yang belum dibuatkan SPK perbaikan.

### Audit Log (Admin)

Halaman ini menampilkan **seluruh** jejak aksi bisnis penting di dalam sistem, lintas semua pengguna. Filter yang tersedia mencakup jenis aksi, pengguna tertentu (khusus untuk peran admin, karena petugas memang hanya pernah melihat aktivitasnya sendiri sehingga filter pengguna tidak relevan bagi mereka), dan rentang tanggal berupa "Dari Tanggal" sampai "Sampai Tanggal" berdasarkan kapan sebuah baris log tercatat.

### System Error Log

Halaman ini mencatat exception yang tidak terduga (level kesalahan 500 ke atas) yang terjadi di dalam aplikasi secara otomatis, memudahkan proses debugging tanpa perlu akses langsung ke server atau berkas log secara manual.

---

## Fitur Petugas Lapangan

### Dashboard: Daftar Surat Aktif

Halaman ini menampilkan **semua** SPK yang berstatus aktif, bukan hanya yang sudah diikuti petugas yang bersangkutan. Ini adalah tempat di mana petugas menemukan pekerjaan baru untuk mereka ambil, sebuah papan pekerjaan yang senantiasa terbuka bagi siapa saja.

Halaman ini menyajikan ringkasan: jumlah tugas yang sedang aktif, jumlah tugas yang sedang dalam progres, jumlah tugas yang mendekati tenggat waktu, dan jumlah tugas yang selesai pada bulan berjalan, seluruhnya dihitung khusus untuk tim milik petugas itu sendiri.

Kartu SPK pada halaman ini memakai slideshow foto yang sama seperti versi admin, bersilih-ganti secara otomatis melalui seluruh foto rambu yang ada di dalam SPK tersebut. Kartu ini juga diberi lingkaran berwarna dan lencana yang membedakan tiga keadaan: **"Sudah Bergabung"** (petugas ini sendiri sudah menjadi anggota tim, ditandai lingkaran biru solid), **"Sudah Ada Tim Lain"** (ada tim yang sudah terdaftar namun bukan tim petugas ini, ditandai lingkaran kuning), atau **"Belum Ada Tim"** (belum ada siapa pun yang mengambilnya, ditandai netral). Pembedaan ini penting supaya keadaan "belum ada yang mengambil" dan "sudah diambil orang lain" tidak terlihat sama di mata petugas yang sedang mencari pekerjaan baru.

Setiap SPK yang timnya sudah bergabung, dan seluruh rambu di dalamnya sudah tertangani (baik lewat laporan pengerjaan maupun kendala) namun belum diajukan sebagai Laporan Akhir, akan menampilkan lencana tambahan bertuliskan **"Siap Diajukan Laporan Akhir"**, sebuah pengingat halus bagi tim yang bersangkutan bahwa langkah berikutnya sudah menanti. Perlu ditekankan, lencana status utama pada kartu ini tidak akan pernah menampilkan "Menunggu Validasi" hanya karena satu rambu sudah dilaporkan; ia baru menampilkan itu setelah SPK-nya benar-benar diajukan sebagai Laporan Akhir dan resmi masuk ke antrean admin, sesuai dengan prinsip validasi per-batch yang dijelaskan pada [ALUR-BISNIS.md](ALUR-BISNIS.md).

### Detail SPK & Gabung Tim

Perwakilan tim mendaftarkan diri sekaligus rekan setimnya dalam satu langkah yang sama (`daftarkanTim`), bukan setiap orang bergabung secara terpisah satu per satu. Sebuah modal konfirmasi selalu ditampilkan sebelum pendaftaran ini benar-benar dieksekusi, mengingat belum ada cara untuk keluar dari peran perwakilan lewat sistem setelah mendaftar.

Perwakilan bisa menambahkan anggota belakangan (`tambahAnggota`), disertai konfirmasi, dan wajib memilih minimal satu anggota, dengan pesan peringatan (toast) apabila formulir dikirim kosong atau seluruh anggota yang dipilih ternyata sudah ada di dalam tim. Perwakilan juga bisa menghapus anggota non-perwakilan dari tim (`hapusAnggota`), misalnya apabila ada kesalahan input nama sebelumnya. Baris keanggotaan perwakilan itu sendiri tidak bisa dihapus lewat aksi ini. Anggota yang dihapus akan menerima notifikasi, dan aksi ini tercatat ke dalam Audit Log.

Halaman ini juga menyediakan tombol untuk mengunduh Surat Pengantar dalam bentuk PDF, yang dihasilkan secara langsung (on-the-fly) setiap kali diminta. Untuk SPK yang berstatus **Selesai**, halaman ini turut menampilkan Durasi Pengerjaan dan Selisih dari Deadline, sama seperti pada versi admin. Apabila ada rambu yang ditolak admin saat proses validasi, alasan penolakannya ditampilkan langsung pada kartu rambu yang bersangkutan. Apabila sebuah rambu sedang tertunda karena adanya kendala, alasan kendalanya juga ditampilkan pada kartu yang sama, sebuah penyempurnaan dibandingkan sebelumnya di mana informasi ini hanya bisa dilihat lewat formulir Kendala atau halaman Validasi milik admin.

### Form Laporan Pengerjaan

Formulir ini **hanya bisa diisi oleh perwakilan tim** (`is_perwakilan = true`). Anggota lain boleh ikut bekerja secara fisik di lapangan, namun yang mengirimkan laporan resmi hanya satu orang saja per SPK. Formulir ini mewajibkan foto hasil akhir pekerjaan, koordinat GPS, catatan lapangan, dan daftar barang atau bahan yang digunakan bila relevan. Mengirimkan formulir ini mengubah status rambu menjadi `menunggu_validasi`.

Formulir ini bisa diedit kembali (foto baru bersifat opsional, sementara field lain akan menimpa data lama) selama SPK yang menaunginya belum mengajukan Laporan Akhir. Apabila dibuka pada sebuah rambu yang sedang berstatus Tertunda karena adanya kendala yang tercatat, mengisi formulir ini akan secara otomatis menggantikan kendala tersebut menjadi sebuah laporan pekerjaan yang telah selesai.

### Form Kendala

Formulir ini juga **hanya bisa diisi oleh perwakilan tim**, sama seperti Laporan Pengerjaan. Apabila pemasangan atau perbaikan tidak bisa dilanjutkan di lapangan karena suatu sebab, formulir ini mewajibkan foto dan alasan yang wajib dijelaskan. Mengirimkan formulir ini mengubah status rambu menjadi `tertunda`. Sama seperti Laporan Pengerjaan, formulir ini bisa diedit kembali, dan bisa pula dipakai untuk membatalkan laporan yang sudah terlanjur dikirim sebelumnya (menggantinya menjadi sebuah kendala), selama SPK yang menaunginya belum mengajukan Laporan Akhir.

### Ajukan Laporan Akhir

Aksi ini hanya bisa diajukan oleh perwakilan tim, dan hanya apabila **seluruh** rambu di dalam SPK sudah berstatus `tertunda` atau `menunggu_validasi` (tidak boleh ada satu pun yang masih `belum` atau `revisi`). Aksi inilah yang memasukkan SPK tersebut ke dalam antrean Validasi Pengerjaan milik admin.

### Lapor Temuan Kondisi

Fitur ini melaporkan rambu yang ditemukan dalam kondisi rusak di lapangan, sepenuhnya **independen dari SPK aktif** mana pun yang sedang dikerjakan petugas. Foto wajib disertakan. Melaporkan sebuah temuan secara langsung mengubah `rambu.kondisi_terkini` menjadi `rusak`, membuat pin rambu tersebut segera berubah warna di peta.

### Riwayat Pekerjaan Saya

Halaman ini menampilkan seluruh SPK yang pernah dikerjakan oleh tim petugas yang bersangkutan, sebagai bukti kerja yang bisa ditunjukkan kepada atasan. Filter yang tersedia berupa rentang tanggal "Dari Tanggal" sampai "Sampai Tanggal", didasarkan pada kapan petugas tersebut bergabung ke masing-masing SPK. Apabila kedua kolom tanggal ini dibiarkan kosong, halaman ini menampilkan **seluruh riwayat pekerjaan** yang pernah ada, bukan dibatasi ke periode tertentu secara diam-diam.

### SPK Sedang Dikerjakan

Halaman ini menampilkan daftar SPK yang timnya sudah terdaftar untuk dikerjakan, sebuah subset dari halaman Dashboard yang hanya menyaring SPK yang memang sudah diikuti oleh petugas yang bersangkutan.

---

## Fitur Bersama (Admin & Petugas)

### Peta Interaktif

Seluruh rambu ditampilkan sebagai pin pada sebuah peta interaktif, dibangun di atas Leaflet dan menggunakan tile dari OpenStreetMap, dengan warna yang mencerminkan status masing-masing.

| Warna | Artinya |
|---|---|
| Cyan | Menunggu Validasi, diprioritaskan di atas merah karena laporan sudah dikirim dan kini menunggu giliran admin |
| Merah | Urgent, Prioritas, atau Urgensi Tinggi |
| Kuning | Kondisi rusak, atau sedang dalam proses perbaikan |
| Biru tua | Selesai dan kondisinya baik |
| Abu-abu | Belum dikerjakan |

Mengklik sebuah pin akan membuka kartu informasi berisi foto, jenis rambu, lokasi, koordinat, dan statusnya, dan mengklik lagi atau menekan tombol **X** pada kartu akan menutupnya kembali. Kartu ini secara otomatis membuka dirinya sendiri di sisi kiri atau kanan pin, tergantung ruang kosong yang tersedia di layar, sehingga kartu tidak pernah terpotong keluar dari tampilan. Di dalam kartu tersebut, tersedia tombol **Detail Rambu** dan **Google Maps**. Khusus untuk petugas, ada tombol tambahan **Lapor Temuan Kondisi** yang bisa langsung diklik dari dalam kartu pin.

### Detail Rambu

Foto utama yang ditampilkan pada halaman ini otomatis mengikuti kondisi terkini: foto sesudah dari laporan pengerjaan terbaru (apabila ada) selalu menang atas foto survei, sehingga begitu sebuah rambu selesai dikerjakan, halaman ini otomatis menampilkan hasil akhirnya, bukan foto sebelum dikerjakan. Penjelasan lengkap tentang logika ini tersedia pada [ALUR-BISNIS.md](ALUR-BISNIS.md).

Halaman ini juga menampilkan riwayat foto dan kondisi, serta riwayat pekerjaan, di mana setiap entri riwayat dihubungkan langsung ke Detail SPK terkait. Apabila sebuah rambu sedang menunggu validasi atau tertunda, admin akan mendapatkan tombol **Ke Halaman Validasi** yang langsung mengarah ke SPK yang bersangkutan.

### Notifikasi

Ikon lonceng pada bagian header dilengkapi lencana bulat berwarna merah yang menampilkan jumlah notifikasi yang belum dibaca (ditampilkan maksimal sebagai "9+"). Notifikasi mencakup berbagai peristiwa seperti SPK baru yang tersedia, Laporan Akhir yang masuk, laporan yang diterima atau ditolak, temuan kondisi, dan lain sebagainya, tergantung pada peran penerimanya. Perlu dicatat, kendala atau laporan pengerjaan yang dikirim petugas per rambu **tidak** memicu notifikasi tersendiri kepada admin, karena admin baru bisa dan baru perlu bertindak setelah seluruh SPK-nya diajukan sebagai Laporan Akhir, sehingga notifikasi per rambu sebelum itu hanya akan menjadi gangguan yang tidak perlu.

Daftar notifikasi ditampilkan sebagai kartu-kartu terpisah, mirip dengan pusat notifikasi pada ponsel pintar. Kartu yang memiliki tujuan halaman tertentu bisa diklik di mana saja pada kartunya (bukan lewat tombol "Lihat" yang terpisah) untuk sekaligus menandainya terbaca dan membuka halaman yang relevan. Tombol "Tandai Dibaca" tetap tersedia secara terpisah untuk notifikasi yang belum dibaca. Setiap pengguna bisa menghubungkan akunnya ke **Telegram** lewat halaman Settings, sehingga notifikasi yang sama juga akan masuk sebagai pesan chat, bukan hanya tampil di dalam halaman ini.

### Riwayat Aktivitas (Audit Log)

Admin bisa melihat seluruh aktivitas sistem tanpa terkecuali. Petugas hanya bisa melihat aktivitas yang mereka lakukan **sendiri**, bukan aktivitas admin atau rekan setim mereka sekali pun berada di SPK yang sama.

### Jenis Rambu & Daftar Rambu

Kedua halaman ini bisa diakses baik oleh admin maupun petugas, berfungsi sebagai referensi bersama. Bagi petugas, kedua halaman ini bersifat baca-saja (read-only), tanpa kemampuan untuk mengubah atau menghapus data apa pun.

### Settings

Halaman Settings terbagi menjadi beberapa bagian: **Profil** untuk mengubah nama, foto, dan data diri lainnya; **Keamanan** untuk mengganti kata sandi dan mengaktifkan atau mengelola Autentikasi Dua Faktor, yang mensyaratkan konfirmasi ulang kata sandi terlebih dahulu sebelum bisa diakses (lihat [KEAMANAN.md](KEAMANAN.md) untuk penjelasan lebih lanjut); dan **Telegram**, tempat pengguna menghubungkan akun Telegram-nya lewat sebuah tautan sekali pakai (dengan membuka bot lalu menekan tombol Start), sehingga seluruh notifikasi yang sudah dibuat di dalam sistem juga otomatis terkirim ke chat tersebut. Hubungan ini bisa diputuskan kapan saja sesuai keinginan pengguna.
