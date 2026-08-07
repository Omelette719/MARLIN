# Alur Bisnis Sistem MARLIN

Penjelasan detail siklus hidup SPK dan aturan bisnis di baliknya, untuk memahami *kenapa* sistem berperilaku seperti yang dijelaskan di [FITUR.md](FITUR.md). Diverifikasi langsung dari kode per 2026-08-07.

---

## Siklus Hidup SPK

```
Admin buat SPK (pasang baru / perbaikan)
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
   (rambu yang tertunda/kendala TIDAK BISA dicentang — lihat di bawah)
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

## Aturan Kunci

### Siapa yang boleh mengirim laporan?

Bergabung ke tim SPK (`daftarkanTim`) bisa dilakukan siapa saja yang mendaftar sebagai **perwakilan**, perwakilan itu sekaligus mendaftarkan rekan setimnya dalam satu aksi. Tapi **hanya perwakilan** (`dikerjakan_oleh.is_perwakilan = true`) yang bisa:
- Mengisi Form Laporan Pengerjaan
- Mengisi Form Kendala
- Mengajukan Laporan Akhir

Anggota tim non-perwakilan bisa ikut bekerja fisik di lapangan dan melihat detail SPK, tapi tidak mengoperasikan form-form di atas. Ini satu titik akuntabilitas per SPK, jelas siapa yang bertanggung jawab melaporkan.

Mendaftarkan diri sebagai perwakilan tidak bisa dibatalkan lewat sistem (tidak ada fitur "keluar tim"), jadi aksi ini minta konfirmasi dulu di UI sebelum benar-benar dieksekusi. Anggota non-perwakilan beda: perwakilan bisa menambah mereka belakangan (`tambahAnggota`), dan juga menghapus mereka lagi kalau ternyata salah input (`hapusAnggota`), keduanya lewat konfirmasi UI juga. Baris `is_perwakilan = true` sendiri tidak pernah bisa dihapus lewat `hapusAnggota`, cuma baris anggota biasa.

### Kenapa ada gate "Laporan Akhir" sebelum validasi?

Admin tidak memvalidasi rambu satu-satu begitu petugas submit laporan, validasi dilakukan **per-batch, per-SPK**, setelah SEMUA rambu dalam SPK itu sudah ditangani (baik lewat laporan pengerjaan maupun kendala). Halaman **Validasi Laporan** (daftar) hanya menampilkan SPK yang `laporan_akhir_diajukan_at` sudah terisi, jadi admin tidak perlu bolak-balik memvalidasi rambu yang baru sebagian selesai.

> Catatan teknis: halaman **Detail Validasi** sendiri sebenarnya bisa diakses langsung lewat URL walau `laporan_akhir_diajukan_at` belum diisi (tidak ada pengecekan tambahan di `mount()`), tapi alur normal aplikasi selalu mengarahkan admin lewat halaman daftar yang sudah terfilter, jadi ini bukan celah yang berdampak nyata.

### Kenapa `laporan_akhir_diajukan_at` di-reset ke `null` setiap kali admin validasi?

Supaya kalau ada rambu yang ditolak (`revisi`), SPK itu otomatis "keluar" dari antrean validasi (karena gate-nya di-reset) sampai perwakilan mengajukan ulang laporan akhir setelah merevisi rambu yang ditolak. Ini mencegah admin memvalidasi ulang rambu yang sama berkali-kali tanpa perwakilan benar-benar menandai "sudah saya perbaiki, tolong cek lagi".

### "Siap diajukan" tetap menghitung rambu yang sudah selesai di putaran sebelumnya

Syarat tombol **Ajukan Laporan Akhir** muncul: minimal satu rambu berstatus `tertunda`/`menunggu_validasi` (ada sesuatu yang baru mau diajukan), DAN tidak ada satupun rambu yang masih `belum`/`revisi` (semua sudah ditangani). Rambu yang sudah `selesai` dari putaran validasi sebelumnya **tetap dihitung sebagai "sudah ditangani"**, bukan diabaikan. Ini penting: SPK dengan beberapa rambu, di mana sebagian sudah `selesai` dan sebagian lagi baru selesai direvisi setelah ditolak, harus tetap bisa diajukan ulang, bukan terkunci permanen karena ada campuran status `selesai` dan `tertunda`/`menunggu_validasi`.

### Validasi menampilkan semua rambu dalam SPK, bukan cuma yang baru masuk

Halaman **Detail Validasi** menampilkan seluruh rambu dalam SPK itu (termasuk yang sudah `selesai` dari putaran sebelumnya), bukan cuma rambu yang statusnya `tertunda`/`menunggu_validasi` saat ini. Ini supaya admin punya konteks penuh saat memutuskan, terutama untuk SPK yang sudah melalui satu atau lebih putaran revisi sebagian.

### Perbaikan per-rambu, bukan per-SPK

Kalau satu rambu ditolak validasinya, **hanya rambu itu** yang statusnya kembali ke `revisi`, rambu lain dalam SPK yang sama yang sudah `selesai` tidak ikut terganggu. SPK baru berubah jadi `selesai` kalau **benar-benar semua** `rambu_pasang` di dalamnya sudah `selesai` atau `batal`; saat itu terjadi, `spk.selesai_pada` dicatat sekali (dipakai untuk menghitung Durasi Pengerjaan & Selisih dari Deadline di halaman Detail Surat).

Alasan penolakan (`catatan_penolakan`) yang diisi admin tidak cuma tersimpan di database dan terkirim lewat notifikasi, tapi juga ditampilkan langsung di kartu rambu terkait pada Detail SPK petugas, dan di form Kendala/Laporan Pengerjaan-nya, supaya jelas apa yang perlu diperbaiki.

Alasan kendala (`kendala.alasan`, beda dengan `catatan_penolakan` di atas, ini yang diisi **petugas** waktu melapor kalau pemasangan/perbaikan tidak bisa dilanjutkan) juga ditampilkan di Detail SPK, baik versi admin maupun petugas, dengan gaya warning-callout yang sama seperti di halaman Validasi. Sebelumnya alasan ini cuma terlihat lewat halaman Validasi Pengerjaan atau dengan membuka ulang form Kendala-nya, sekarang siapapun yang membuka Detail SPK bisa langsung tahu kenapa satu rambu Tertunda tanpa harus mencari-cari.

### Rambu yang terkendala tidak pernah bisa "diterima" saat validasi

Di halaman Detail Validasi, rambu berstatus `tertunda` (ada kendala) **tidak bisa dicentang sesuai/diterima sama sekali** — kartunya sengaja tidak punya interaksi klik, cuma menampilkan badge "Akan dikembalikan untuk direvisi". Alasannya sederhana: kendala artinya pekerjaan itu memang belum selesai dikerjakan, jadi tidak ada laporan pengerjaan nyata yang bisa "diterima" sebagai bukti selesai. Rambu kendala selalu masuk ke jalur penolakan (`revisi`) begitu admin lanjut memproses, dan admin **wajib** mengisi catatan penolakan untuknya sama seperti rambu laporan pengerjaan yang ditolak — biasanya berisi instruksi/arahan untuk mengatasi kendalanya, bukan berarti pekerjaannya "salah".

Ini juga dijaga di sisi server (`Admin\Validasi\Show::normalisasiCheckedKendala()`), bukan cuma di tampilan: begitu admin klik "Proses Validasi" atau "Konfirmasi & Selesaikan", status "dicentang" untuk rambu manapun yang sedang `tertunda` dipaksa jadi tidak-dicentang, terlepas dari apa yang dikirim client — supaya tidak ada jalan, sengaja atau tidak, untuk menandai kendala sebagai selesai tanpa laporan pengerjaan sungguhan.

### Perpanjangan Deadline Saat Menolak Validasi

Selain lewat Edit Surat, admin juga bisa memperpanjang `deadline` SPK langsung dari **Form Penolakan** di halaman Detail Validasi, lewat checkbox opsional "Beri kelonggaran, perpanjang deadline SPK ini juga". Ini dipisah dari alur Edit Surat supaya admin tidak perlu keluar dari konteks "kenapa saya menolak rambu ini" hanya untuk memberi waktu tambahan yang wajar untuk revisinya.

Kalau dicentang dan tanggal baru diisi:
- `deadline` **dan** `deadline_asli` SPK sama-sama diperbarui (konvensi yang sama dipakai Edit Surat dan `PenyesuaianDeadlineSpk`), lalu `urgensi` dihitung ulang dari deadline baru.
- Tercatat di Audit Log (`deadline_diperpanjang`).
- Seluruh tim SPK dapat notifikasi bahwa deadline-nya berubah.
- Perpanjangan berlaku untuk **seluruh SPK**, bukan cuma rambu yang sedang direvisi, karena `deadline` memang atribut di level SPK, bukan per rambu.

Perubahan deadline ini dan proses penolakan rambunya sendiri dibungkus dalam **satu transaksi database**, supaya deadline yang sudah berubah tidak pernah "nyangkut" tanpa penolakan yang menyertainya benar-benar tercatat, atau sebaliknya.

### Laporan pengerjaan bisa berlapis, dan bisa diedit ulang sebelum Laporan Akhir diajukan

Satu `rambu_pasang` bisa punya lebih dari satu baris `laporan_pengerjaan` seiring waktu, kalau laporan pertama ditolak, laporan revisi berikutnya jadi baris BARU (bukan menimpa yang lama), jadi riwayat penolakan tetap tersimpan lengkap.

Selama SPK belum mengajukan Laporan Akhir, petugas juga bisa membuka ulang Form Kendala/Laporan Pengerjaan untuk rambu yang statusnya `tertunda` atau `menunggu_validasi` dan mengedit isinya, atau bahkan menukar jenis laporannya (mis. ternyata bukan kendala, pekerjaannya sudah selesai) tanpa perlu menunggu admin menolak dulu.

## Temuan Kondisi (Independen dari SPK Aktif)

Petugas bisa melaporkan rambu yang ditemukan rusak **kapan saja**, tidak terikat pada SPK aktif mereka (mis. ditemukan saat patroli, bukan sedang mengerjakan SPK apapun), dan **bisa dilakukan siapa saja**, bukan cuma perwakilan (beda dengan Kendala/Laporan yang perwakilan-only).

Begitu dilaporkan:
1. `rambu.kondisi_terkini` langsung berubah jadi `rusak`, supaya pin di peta segera berubah warna, tanpa menunggu admin memproses apapun.
2. Semua admin dapat notifikasi (juga lewat Telegram kalau sudah dihubungkan).
3. Laporan masuk ke antrean **Temuan Lapangan** admin dengan `status_tindak_lanjut = baru`.
4. Admin bisa membuat SPK perbaikan baru yang merujuk ke `laporan_kondisi_id` ini, begitu SPK dibuat, `status_tindak_lanjut` berubah jadi `sudah_dibuatkan_spk`. Admin juga bisa menolak temuan itu langsung (`status_tindak_lanjut = ditolak`) kalau memang tidak perlu ditindaklanjuti; pelapornya dapat notifikasi juga.

Foto yang sudah dilampirkan petugas saat melapor temuan **ikut terbawa** ke SPK perbaikan yang dibuat dari temuan itu: disalin jadi `foto_survei` milik `rambu_pasang` barunya (bukan cuma referensi ke path yang sama), jadi admin tidak perlu upload ulang foto yang sebenarnya sudah ada. Admin tetap bisa menimpanya dengan foto lain kalau upload foto baru saat mengisi form.

## Urgensi Otomatis

`urgensi` pada SPK **selalu dihitung otomatis** (admin tidak bisa set manual), berdasarkan sisa hari ke `deadline`:

| Kondisi | Urgensi |
|---|---|
| Ditandai **Prioritas** | Tinggi (terlepas dari deadline) |
| Sisa ≤ 2 hari | Tinggi |
| Sisa ≤ 7 hari | Sedang |
| Selebihnya | Rendah |

Kolom `urgensi` di database sendiri cuma diisi ulang saat SPK dibuat/di-edit/deadline-nya berubah (lewat perpanjangan manual di atas atau `PenyesuaianDeadlineSpk`), jadi nilainya bisa jadi basi begitu waktu berjalan (mis. SPK yang tadinya Sedang jadi seharusnya Tinggi begitu sisa harinya tinggal 2 hari, tanpa ada yang mengedit apapun). Untuk SPK yang masih **Aktif**, semua tempat yang menampilkan urgensi (badge di kartu SPK, pin peta, sorting "butuh perhatian" di Dashboard Admin) memanggil `Spk::urgensiSaatIni()`, yang menghitung ulang secara live dari deadline+prioritas saat ini alih-alih membaca kolom `urgensi` yang tersimpan. Untuk SPK yang sudah **Selesai**/**Dibatalkan**, `urgensiSaatIni()` mengembalikan nilai `urgensi` yang tersimpan apa adanya (dibekukan), karena menghitung ulang urgensi pekerjaan yang sudah final tidak ada gunanya.

## Warna Pin di Peta: Urutan Prioritas

Warna dihitung di sisi client (JavaScript), **bukan** kolom tersimpan di database, supaya selalu real-time tanpa perlu query tambahan. Urutan pengecekan (yang pertama cocok, itu yang dipakai):

1. **Cyan**: `rambu_pasang.status = menunggu_validasi` (menang atas segalanya, termasuk prioritas/urgent, karena laporan sudah dikirim, giliran admin, tidak perlu terus terlihat "darurat")
2. **Merah**: `status = urgent`, atau SPK-nya `prioritas = true`, atau `urgensi = tinggi`
3. **Kuning**: `kondisi_terkini = rusak`, atau sedang `perbaikan` yang belum `selesai`
4. **Biru tua**: `status = selesai` (atau tidak ada tugas aktif) DAN `kondisi_terkini = baik`
5. **Abu-abu**: selain semua di atas (belum dikerjakan)

> Urutan ini **sengaja berbeda** dari draft spesifikasi awal proyek (yang menyebut merah selalu menang atas semua warna lain). Diputuskan lewat diskusi bahwa status "menunggu validasi" harus tetap terlihat progress-nya di peta, bukan tertutup warna merah selama menunggu giliran admin.

Peta pakai tile OpenStreetMap standar. Widget peta ringkas di Dashboard Admin punya tombol **Unduh PDF** yang mengambil cuplikan gambar peta yang sedang tampil (lewat `leaflet-image`, dengan ikon pin disintesis ulang jadi gambar tersendiri karena pin aslinya elemen HTML, bukan file gambar) dan menyertakannya di laporan PDF sebaran rambu.

## Foto Rambu yang Ditampilkan: "Terkini" vs "Beku Sejak Dibuat"

Sistem sengaja membedakan dua konsep foto yang berbeda, jangan disamakan:

**Foto rambu "terkini"** (kartu info pin Peta, halaman Detail Rambu) dihitung lewat `Rambu::fotoUtama()`: untuk `rambu_pasang` paling baru milik rambu itu, foto sesudah (`laporan_pengerjaan.foto_sesudah`) dari laporan pengerjaan paling baru **selalu menang** atas foto survei (`foto_survei`) rambu_pasang itu sendiri, karena laporan pengerjaan selalu dibuat belakangan setelah survei, jadi lebih mencerminkan kondisi rambu sekarang. Kalau belum ada laporan pengerjaan sama sekali, baru jatuh ke foto survei; kalau `rambu_pasang` itu juga tidak punya foto survei, baru dicoba `rambu_pasang` yang lebih lama. Ini artinya begitu satu rambu selesai dikerjakan dan divalidasi, foto yang tampil di Peta/Detail Rambu otomatis berganti dari "sebelum" ke "sesudah" tanpa perlu aksi tambahan apapun.

**Foto SPK/Detail SPK** (kartu daftar SPK, "Daftar Rambu" di Detail SPK, Surat Pengantar PDF) **sebaliknya selalu beku**: cuma pakai `rambu_pasang.foto_survei` apa adanya, tidak pernah ikut berubah walau rambu.nya sudah dikerjakan/foto sesudahnya sudah ada. Ini disengaja, dokumen dan kartu terkait satu SPK harus tetap menggambarkan kondisi "sejak SPK ini dibuat", bukan kondisi rambu yang terus berubah seiring waktu.

## Pembatalan SPK

Admin bisa membatalkan seluruh SPK yang masih **aktif** (tombol Batalkan SPK di Detail Surat, minta konfirmasi dulu). Efeknya:
1. `spk.status = dibatalkan`. **Tidak pernah** `DELETE` baris SPK.
2. Semua `rambu_pasang` yang belum `selesai`/`batal` ditandai `batal`.
3. Rambu `pasang_baru` yang belum pernah benar-benar terpasang (`sudah_terpasang = false`) otomatis hilang dari peta, karena tidak pernah jadi objek fisik nyata.
4. Rambu `perbaikan` tetap tampil di peta apa adanya, `kondisi_terkini` tetap `rusak`, menunggu SPK perbaikan lain di masa depan.
5. Semua anggota tim yang sudah gabung dapat notifikasi bahwa SPK yang mereka kerjakan dibatalkan.

## Pembatalan Satu Rambu (Bukan Seluruh SPK)

Dari halaman Edit Surat, admin juga bisa membatalkan **satu baris rambu saja** tanpa membatalkan seluruh SPK, misalnya kalau lokasinya ternyata sudah ada rambu lain atau tidak jadi dikerjakan. Beda dengan Batalkan SPK:
- Wajib isi `catatan_pembatalan` (alasan) lewat modal konfirmasi, tersimpan di baris `rambu_pasang` itu sendiri.
- Rambu lain dalam SPK yang sama, dan status SPK-nya sendiri, tidak terpengaruh sama sekali.
- Alasan pembatalannya ditampilkan di kartu rambu pada Detail Surat, di kolom Info surat pengantar (PDF), dan di kolom Keterangan Laporan Rambu, supaya siapapun yang baca dokumennya tahu kenapa rambu itu tidak jadi dikerjakan.

Kalau rambu itu murni salah input (status masih `belum`/`batal`, belum pernah ada kendala atau laporan pengerjaan sama sekali), admin bisa **menghapus permanen** baris `rambu_pasang`-nya alih-alih membatalkan. Baris `rambu` fisiknya sendiri tidak ikut terhapus (tetap ada sebagai aset, cuma tidak lagi terkait SPK ini).
