# Dokumentasi Sistem MARLIN

Selamat datang di folder dokumentasi Sistem MARLIN. Folder ini menyimpan seluruh dokumentasi teknis dan fungsional sistem, dipecah menjadi beberapa berkas terpisah berdasarkan topiknya masing-masing, dengan tujuan supaya setiap dokumen bisa dibaca secara mandiri sesuai kebutuhan pembaca, tanpa harus menelusuri satu berkas raksasa yang mencampuradukkan segalanya menjadi satu.

Jika Anda benar-benar baru mengenal proyek ini dan belum pernah membacanya sama sekali, sebaiknya mulai dari [README.md](../README.md) yang berada di direktori akar proyek terlebih dahulu. Dokumen itu memberikan gambaran umum tentang apa itu MARLIN, mengapa sistem ini dibangun, dan bagaimana cara menjalankannya di komputer Anda sendiri. Setelah memahami gambaran besarnya, barulah dokumen-dokumen di folder ini akan lebih mudah dicerna, karena setiap dokumen di sini mengasumsikan Anda sudah punya pemahaman dasar tentang apa itu Surat Perintah Kerja (SPK), siapa saja aktor yang terlibat, dan bagaimana alur kerja secara garis besar berjalan.

## Daftar Dokumen dan Isinya

Setiap dokumen di bawah ini ditulis dengan tujuan yang berbeda-beda, dan sengaja disusun agar saling melengkapi tanpa banyak duplikasi. Berikut penjelasan singkat tentang apa yang bisa Anda temukan di masing-masing dokumen.

| Dokumen | Isi |
|---|---|
| [FITUR.md](FITUR.md) | Rincian lengkap setiap fitur yang tersedia di sistem, dikelompokkan berdasarkan peran penggunanya, yaitu admin, petugas lapangan, dan fitur yang dipakai bersama oleh keduanya. |
| [HALAMAN.md](HALAMAN.md) | Referensi lengkap untuk setiap halaman dalam sistem: alamat URL-nya, siapa saja yang diizinkan mengaksesnya, dan penjelasan singkat tentang fungsinya. |
| [ALUR-BISNIS.md](ALUR-BISNIS.md) | Penjelasan paling mendalam tentang siklus hidup SPK dan seluruh aturan bisnis yang melatarbelakangi perilaku sistem, termasuk alasan-alasan mengapa suatu keputusan desain diambil. |
| [DAFTAR-AKTIVITAS.md](DAFTAR-AKTIVITAS.md) | Katalog lengkap aktivitas dan interaksi, dikelompokkan per pasangan aktor (Petugas dengan Sistem, Admin dengan Sistem, Admin dengan Petugas, dan sebagainya), disusun sebagai bahan mentah untuk penyusunan diagram aktivitas. |
| [DATABASE.md](DATABASE.md) | Skema lengkap setiap tabel dalam basis data, mencakup nama kolom, tipe data, nilai bawaan, dan aturan foreign key. |
| [KEAMANAN.md](KEAMANAN.md) | Penjelasan tentang langkah-langkah keamanan yang sudah diterapkan di dalam sistem, serta keputusan-keputusan desain terkait akses data yang sengaja diambil. |
| [DFD.md](DFD.md) | Diagram Alir Data (Data Flow Diagram) sistem, mulai dari diagram konteks tingkat tertinggi sampai dekomposisi yang lebih terperinci untuk proses-proses bisnis yang paling kompleks. |
| [USE-CASE-KEBUTUHAN.md](USE-CASE-KEBUTUHAN.md) | Daftar use case dalam bentuk user story, kebutuhan fungsional (functional requirements), dan kebutuhan non-fungsional (non-functional requirements) sistem. |
| [SKENARIO-UAT.md](SKENARIO-UAT.md) | Skenario pengujian penerimaan pengguna (User Acceptance Testing) untuk peran admin maupun petugas lapangan, disusun dengan kriteria penerimaan yang bisa langsung dicentang oleh penguji. |
| [STRUKTUR-BUKU-PANDUAN.md](STRUKTUR-BUKU-PANDUAN.md) | Rancangan struktur bab dan subbab untuk penyusunan buku panduan pengguna, mengikuti urutan menu yang sesungguhnya ada di sistem. |

## Urutan Baca yang Disarankan

Karena setiap dokumen ditulis dengan asumsi pembaca sudah memahami dokumen-dokumen sebelumnya sampai tingkat tertentu, kami menyarankan urutan membaca berikut ini apabila Anda benar-benar baru pertama kali berkenalan dengan proyek ini.

Mulailah dari [README.md](../README.md) di direktori akar proyek untuk memahami apa itu MARLIN secara umum dan bagaimana cara menjalankannya. Setelah itu, lanjutkan ke [ALUR-BISNIS.md](ALUR-BISNIS.md) untuk benar-benar memahami siklus SPK dari awal sampai akhir, karena hampir seluruh dokumen lain merujuk balik ke pemahaman ini. Dari sana, [FITUR.md](FITUR.md) akan memberi Anda gambaran rinci tentang setiap fitur per peran pengguna, dilanjutkan dengan [HALAMAN.md](HALAMAN.md) yang memetakan setiap fitur tersebut ke alamat URL dan aturan aksesnya secara konkret.

Apabila tujuan Anda adalah menyusun diagram aktivitas atau memahami interaksi antar aktor secara lebih sistematis, [DAFTAR-AKTIVITAS.md](DAFTAR-AKTIVITAS.md) adalah tempat yang tepat untuk dituju selanjutnya. Ketika Anda perlu mengubah atau menambah struktur basis data, pastikan untuk membaca [DATABASE.md](DATABASE.md) terlebih dahulu supaya perubahan yang Anda buat tetap konsisten dengan konvensi yang sudah ada. Dan sebelum sistem ini benar-benar dipasang ke lingkungan produksi, [KEAMANAN.md](KEAMANAN.md) wajib dibaca sampai tuntas, karena di sanalah dijelaskan mana saja batasan yang memang disengaja dan mana yang perlu diperkuat sebelum diakses lewat internet publik.
