# Fitur Sistem MARLIN

Dokumen ini merinci setiap fitur per peran. Untuk gambaran umum sistem, lihat [README.md](../README.md). Untuk penjelasan tiap halaman/route, lihat [HALAMAN.md](HALAMAN.md). Untuk alur bisnis lengkap, lihat [ALUR-BISNIS.md](ALUR-BISNIS.md).

---

## Daftar Isi

- [Fitur Admin](#fitur-admin)
- [Fitur Petugas Lapangan](#fitur-petugas-lapangan)
- [Fitur Bersama (Admin & Petugas)](#fitur-bersama-admin--petugas)

---

## Fitur Admin

### Dashboard Admin
Ringkasan jumlah SPK aktif, rambu rusak, dan laporan yang menunggu validasi — titik masuk utama admin ke sistem.

### Buat Surat (SPK)
- Dua jenis: **Pemasangan Baru** dan **Perbaikan**.
- Alamat dipecah jadi field terstruktur: **Jalan**, **RT**, **Kelurahan** — kolom `wilayah` (teks gabungan seperti "Jl. X RT. Y Kel. Z") disusun otomatis dari ketiganya, dipakai di surat pengantar & tampilan lain tanpa perlu ubah kode di tempat lain.
- Banyak baris rambu dalam satu surat sekaligus (tombol **Tambah Rambu** di bagian bawah daftar).
- Untuk **Perbaikan**: bisa pilih rambu yang sudah terdaftar di sistem (searchable-select) atau catat rambu yang secara fisik sudah ada tapi belum pernah tercatat.
- **Perihal Permohonan** opsional — kalau kosong, dibuat otomatis dari jenis pekerjaan & jenis rambu (mis. "pemasangan cermin tikungan").
- **Tanggal Survei** opsional — kalau diisi, muncul di surat pengantar sebagai "(DISURVEI TGL ...)".
- Data **RT/Perwakilan** (nama & telepon) untuk keperluan tanda tangan manual di kertas.
- **Urgensi** dihitung otomatis: ≤2 hari ke deadline atau ditandai **Prioritas** → Tinggi; ≤7 hari → Sedang; selebihnya → Rendah.
- Upload **File Referensi** (scan surat permohonan asli), maks. 5 MB.

### Edit Surat (SPK)
- Hanya tersedia selama status SPK masih **Aktif** (tidak bisa edit SPK yang sudah Selesai/Dibatalkan).
- Bisa ubah: Jalan/RT/Kelurahan, Perihal, Deadline, Prioritas, Asal Permintaan, Keterangan Asal, Tanggal Survei, File Referensi, Catatan, dan data RT/Perwakilan.
- **Tidak bisa** diubah lewat Edit: jenis SPK (pasang baru/perbaikan) dan daftar rambunya — ini terkait langsung ke rambu fisik yang sudah dibuat saat SPK dibuat, mengubahnya lewat form edit berisiko membuat data tidak konsisten.
- Setiap perubahan tercatat di Audit Log (`spk_diedit`).

### Batalkan SPK
- Tombol di halaman Detail Surat, hanya muncul selama status **Aktif**, dengan konfirmasi sebelum eksekusi.
- SPK **tidak pernah dihapus permanen** — statusnya diubah jadi `dibatalkan`.
- Semua `rambu_pasang` yang belum selesai otomatis ditandai `batal`.
- Rambu pasang-baru yang belum pernah benar-benar terpasang otomatis hilang dari peta (karena tidak pernah jadi objek nyata); rambu perbaikan tetap tampil di peta (kondisinya tetap tercatat rusak, menunggu SPK perbaikan lain).
- Tercatat di Audit Log (`spk_dibatalkan`).

### Daftar Surat
- Pencarian (nomor surat/wilayah), filter status, filter jenis pekerjaan.
- **SPK berstatus Selesai diarsipkan** — tidak tampil di daftar default, tapi tetap bisa dilihat dengan memilih status "Selesai" di filter.
- Kartu SPK menampilkan foto (dari foto survei rambu pertama yang punya foto) atau ikon placeholder.

### Validasi Pengerjaan
- Daftar SPK yang sudah mengajukan **Laporan Akhir** (lihat [ALUR-BISNIS.md](ALUR-BISNIS.md) untuk detail gate ini).
- Per rambu, admin centang/tidak-centang untuk terima/tolak.
- Rambu yang tidak dicentang wajib diisi **catatan penolakan** — statusnya kembali ke `revisi`, petugas mengerjakan ulang khusus rambu itu (rambu lain di SPK yang sama tidak terganggu).
- Rambu yang diterima: `pasang_baru` → `rambu.sudah_terpasang = true`; `perbaikan` → `rambu.kondisi_terkini = baik`.
- Kalau semua rambu dalam SPK sudah `selesai`, status SPK otomatis berubah jadi `selesai`.

### Laporan Bulanan
- Rekap rambu terpasang/belum terpasang dan SPK selesai/belum dalam rentang bulan tertentu.
- Bisa diexport ke PDF untuk dilaporkan ke atasan.

### Kelola Jenis Rambu
- Master data kategori rambu: nama, spesifikasi standar, gambar referensi, bentuk ikon di peta (bulat/kotak).

### Kelola Rambu
- Daftar semua rambu terpasang di sistem, filter berdasar kondisi/wilayah.

### Kelola Pengguna
- Tambah/edit akun petugas & admin.
- **Aktifkan/nonaktifkan** akun (bukan hapus) — akun nonaktif tidak bisa login lagi (lihat [KEAMANAN.md](KEAMANAN.md)), tapi sesi yang sudah berjalan tidak otomatis terputus.

### Temuan Kondisi Belum Ditindaklanjuti
- Antrean laporan kerusakan dari petugas (`laporan_kondisi`) yang belum dibuatkan SPK perbaikan.

### Audit Log (Admin)
- Melihat **seluruh** jejak aksi bisnis kunci di sistem (SPK dibuat, laporan dikirim, validasi diterima/ditolak, SPK dibatalkan, dll), lintas semua pengguna.

### System Error Log
- Exception tak terduga (level 500 ke atas) di aplikasi otomatis tercatat di sini — memudahkan debugging tanpa perlu akses server/log file langsung.

---

## Fitur Petugas Lapangan

### Dashboard — Daftar Surat Aktif
- Menampilkan **semua** SPK berstatus aktif (bukan cuma yang sudah diikuti) — ini tempat petugas menemukan pekerjaan baru untuk diambil.
- Ringkasan: jumlah tugas aktif, tugas dalam progres, tugas mendekati deadline, tugas selesai bulan ini (khusus milik tim sendiri).

### Detail SPK & Gabung Tim
- Perwakilan tim mendaftarkan diri + rekan setim sekaligus (`daftarkanTim`) — bukan tiap orang gabung sendiri-sendiri.
- Bisa menambah anggota belakangan (`tambahAnggota`), khusus oleh perwakilan.
- Unduh Surat Pengantar (PDF, dibuat on-the-fly).

### Form Laporan Pengerjaan
- **Hanya bisa diisi oleh perwakilan tim** (`is_perwakilan = true`) — anggota lain boleh ikut kerja fisik di lapangan, tapi yang mengirim laporan cuma satu orang per SPK.
- Foto sesudah (wajib), koordinat GPS, catatan lapangan, daftar barang/bahan yang dipakai.
- Mengubah status rambu jadi `menunggu_validasi`.

### Form Kendala
- **Hanya bisa diisi oleh perwakilan tim**, sama seperti Laporan Pengerjaan.
- Kalau pemasangan/perbaikan tidak bisa dilanjutkan di lapangan — foto wajib, alasan wajib.
- Mengubah status rambu jadi `tertunda`.

### Ajukan Laporan Akhir
- Hanya bisa diajukan oleh perwakilan tim, dan hanya kalau **semua** rambu dalam SPK sudah berstatus `tertunda`/`menunggu_validasi` (tidak ada yang masih `belum`/`revisi`).
- Ini yang memasukkan SPK ke antrean Validasi Pengerjaan admin.

### Lapor Temuan Kondisi
- Laporkan rambu yang ditemukan rusak di lapangan, **independen dari SPK aktif** manapun.
- Foto wajib. Langsung mengubah `rambu.kondisi_terkini = rusak` supaya pin di peta segera berubah warna.

### Riwayat Pekerjaan Saya
- Semua SPK yang pernah dikerjakan tim, bisa difilter per bulan — sebagai bukti kerja.

### SPK Sedang Dikerjakan
- Daftar SPK yang timnya sudah terdaftar untuk mengerjakan (subset dari Dashboard, hanya yang sudah diikuti).

---

## Fitur Bersama (Admin & Petugas)

### Peta Interaktif
- Semua rambu tampil sebagai pin di peta (Leaflet + OpenStreetMap), warna sesuai status:

  | Warna | Arti |
  |---|---|
  | Cyan | Menunggu Validasi (diprioritaskan di atas merah — laporan sudah dikirim, giliran admin) |
  | Merah | Urgent / Prioritas / Urgensi Tinggi |
  | Kuning | Kondisi rusak, atau sedang dalam proses perbaikan |
  | Biru tua | Selesai & kondisi baik |
  | Abu-abu | Belum dikerjakan |

- Klik pin untuk membuka kartu info (foto, jenis rambu, lokasi, koordinat, status) — klik lagi atau tombol **X** di kartu untuk menutup.
- Kartu otomatis membuka di sisi kiri/kanan pin tergantung ruang kosong di layar.
- Tombol **Detail Rambu** dan **Google Maps** di dalam kartu.
- Petugas dapat tombol tambahan **Lapor Temuan Kondisi** langsung dari kartu pin.

### Detail Rambu
- Riwayat foto & kondisi, riwayat pekerjaan (tiap entri link ke Detail SPK terkait).
- Kalau rambu sedang menunggu validasi/tertunda, admin dapat tombol **Ke Halaman Validasi** langsung ke SPK terkait.

### Notifikasi
- Bell icon di header dengan badge bulat merah menampilkan jumlah belum dibaca (maks. tampil "9+").
- Notifikasi meliputi: SPK baru tersedia, laporan diterima/ditolak, kendala diajukan, temuan kondisi, dll — tergantung peran.

### Riwayat Aktivitas (Audit Log)
- Admin melihat seluruh aktivitas sistem.
- Petugas hanya melihat aktivitas yang **dia lakukan sendiri** (bukan aktivitas admin/rekan setim di SPK yang sama).

### Jenis Rambu & Daftar Rambu
- Kedua halaman ini bisa diakses admin maupun petugas (read-only untuk petugas) — referensi bersama.

### Settings
- Profil (nama, foto, data diri) dan Keamanan (ganti password, aktifkan/kelola 2FA) — halaman Keamanan memerlukan konfirmasi ulang password sebelum diakses (lihat [KEAMANAN.md](KEAMANAN.md)).
