# Skema Database Sistem MARLIN

Referensi lengkap tiap tabel domain: kolom, tipe, default, dan aturan hapus (foreign key). Untuk ringkasan singkat, lihat [README.md](../README.md#struktur-database). Untuk nilai-nilai enum yang dipakai, lihat bagian [Enum](#enum) di bawah.

Database dev: SQLite (`database/database.sqlite`). Setiap tabel domain punya satu file migrasi sendiri di `database/migrations/`, dengan urutan penomoran mengikuti dependency foreign key (tabel yang dirujuk selalu dibuat lebih dulu).

---

## Tabel Bawaan Laravel

- `users`: lihat detail di bawah (tabel ini dimodifikasi dari bawaan starter kit, bukan murni bawaan).
- `sessions`, `cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs`: bawaan Laravel standar, tidak dimodifikasi.

## `users`

| Kolom | Tipe | Keterangan |
|---|---|---|
| `name` | string | Nama lengkap |
| `nama_panggilan` | string, nullable | Nama panggilan (ditampilkan di header, mis. "Halo, Budi") |
| `nip` | string, unique | Nomor Induk Pegawai, dipakai untuk login |
| `username` | string, unique, nullable | Belum dipakai untuk fitur apapun saat ini, disiapkan untuk kebutuhan masa depan |
| `role` | string, default `user` | `admin` atau `user` (petugas), lihat enum `Role` |
| `tanggal_lahir` | date, nullable | |
| `jenis_kelamin` | string(1), nullable | `L` atau `P` |
| `bidang` | string, nullable | Bidang/divisi di Dishub |
| `jabatan` | string, nullable | |
| `no_telepon` | string(20), nullable | |
| `aktif` | boolean, default `true` | Nonaktifkan akun tanpa menghapusnya |
| `telegram_chat_id` | string, unique, nullable | Diisi otomatis setelah akun berhasil dihubungkan ke bot Telegram |
| `telegram_link_token` | string, unique, nullable | Token sekali pakai buat proses hubungkan Telegram, dikosongkan lagi begitu berhasil atau digenerate ulang |
| `password` | string | Di-hash otomatis (cast `hashed`) |
| `two_factor_secret`, `two_factor_recovery_codes`, `two_factor_confirmed_at` | (berbagai tipe) | Dikelola Fortify untuk 2FA |

## `jenis_rambu`

Master kategori rambu.

| Kolom | Tipe | Keterangan |
|---|---|---|
| `nama_jenis` | string | |
| `spesifikasi_standar` | text, nullable | |
| `gambar_referensi` | string, nullable | Path file di disk `public` |
| `bentuk_ikon` | string, default `bulat` | `bulat` atau `kotak`, bentuk ikon pin di peta |

**Relasi**: `hasMany` &rarr; `rambu`.

## `rambu`

Satu baris = satu rambu fisik.

| Kolom | Tipe | Keterangan |
|---|---|---|
| `jenis_rambu_id` | FK &rarr; `jenis_rambu`, **restrict**, indexed | |
| `wilayah` | string, indexed | Teks alamat gabungan (disusun otomatis dari `jalan`/`rt`/`kelurahan` lewat trait `ComposesWilayah` kalau kosong) |
| `jalan` | string, nullable | |
| `rt` | string, nullable | |
| `kelurahan` | string, nullable | |
| `lokasi` | string | Lokasi spesifik, mis. "perempatan 1" |
| `koordinat` | string | Format `"lat,lng"` |
| `kondisi_terkini` | string, default `baik` | `baik` atau `rusak`, enum `KondisiRambu` |
| `sudah_terpasang` | boolean, default `false` | |

**Relasi**: `belongsTo` &rarr; `jenis_rambu`; `hasMany` &rarr; `rambu_pasang`, `laporan_kondisi`.

## `spk`

Surat Perintah Kerja: entitas utama sistem.

| Kolom | Tipe | Keterangan |
|---|---|---|
| `nomor_surat` | string, unique | Format `SR-{tahun}/BJM/{urutan 4 digit}` |
| `dibuat_oleh` | FK &rarr; `users`, **restrict** | Selalu admin |
| `wilayah` | string | Sama seperti di `rambu`, komposit otomatis |
| `jalan`, `rt`, `kelurahan` | string, nullable | |
| `deadline` | date | |
| `deadline_asli` | date, nullable | Deadline asli sebelum digeser otomatis oleh `App\Support\PenyesuaianDeadlineSpk` saat SPK prioritas lain dibuat |
| `prioritas` | boolean, default `false` | Kalau `true`, urgensi otomatis `tinggi` |
| `urgensi` | string | `rendah`/`sedang`/`tinggi`, enum `Urgensi`, dihitung otomatis dari deadline+prioritas |
| `status` | string, default `aktif`, indexed | `aktif`/`selesai`/`dibatalkan`, enum `StatusSpk` |
| `jenis_spk` | string, default `pasang_baru` | `pasang_baru`/`perbaikan`, enum `JenisPekerjaan` |
| `asal_permintaan` | string | Lihat enum `AsalPermintaan` |
| `keterangan_asal` | string, nullable | Mis. nama pelapor/instansi |
| `perihal` | string, nullable | Kalau kosong, dibuat otomatis saat render PDF |
| `tanggal_survei` | date, nullable | |
| `petugas_survei` | string, nullable | Nama petugas survei, dicatat manual. Wajib diisi kalau `tanggal_survei` diisi |
| `file_referensi` | string, nullable | Path scan surat permohonan asli |
| `catatan_pekerja_tambahan` | string, nullable | |
| `laporan_akhir_diajukan_at` | timestamp, nullable | Gate untuk masuk antrean validasi, lihat [ALUR-BISNIS.md](ALUR-BISNIS.md) |
| `selesai_pada` | timestamp, nullable | Diisi sekali saat `status` berubah jadi `selesai`. Dipakai untuk menghitung Durasi Pengerjaan & Selisih dari Deadline |

**Relasi**: `hasMany` &rarr; `rambu_pasang`, `dikerjakan_oleh`, `rt_perwakilan`, `audit_log`; `belongsToMany` &rarr; `users` lewat `dikerjakan_oleh`.

**SPK tidak pernah dihapus**, dibatalkan lewat perubahan `status`, bukan `DELETE`.

## `rambu_pasang`

Baris pekerjaan per rambu, dalam konteks satu SPK. Ini "jembatan" antara `spk` dan `rambu`.

| Kolom | Tipe | Keterangan |
|---|---|---|
| `rambu_spk_id` | FK &rarr; `spk`, **cascade**, indexed | Ikut terhapus kalau SPK dihapus (walau SPK sendiri tidak pernah dihapus di aplikasi nyata) |
| `rambu_id` | FK &rarr; `rambu`, **restrict**, indexed | Rambu tidak boleh dihapus selama masih dirujuk |
| `laporan_kondisi_id` | FK &rarr; `laporan_kondisi`, nullable, **restrict** | Diisi kalau SPK ini berasal dari temuan kondisi |
| `jenis_pekerjaan` | string | `pasang_baru`/`perbaikan` |
| `jumlah` | unsigned int, default `1` | |
| `foto_survei` | string, nullable | |
| `catatan_instruksi` | string, nullable | |
| `catatan_pembatalan` | string, nullable | Alasan pembatalan, diisi kalau admin membatalkan rambu ini secara individual (bukan lewat Batalkan SPK) |
| `status` | string, default `belum`, indexed | `belum`/`urgent`/`tertunda`/`menunggu_validasi`/`revisi`/`selesai`/`batal`, enum `StatusRambuPasang` |

**Relasi**: `belongsTo` &rarr; `spk`, `rambu`, `laporan_kondisi`; `hasMany` &rarr; `laporan_pengerjaan`, `kendala`.

## `dikerjakan_oleh`

Pivot petugas &harr; SPK. Hanya `created_at` (`const UPDATED_AT = null`).

| Kolom | Tipe | Keterangan |
|---|---|---|
| `by_spk_id` | FK &rarr; `spk`, **cascade** | |
| `by_user_id` | FK &rarr; `users`, **restrict**, indexed | |
| `is_perwakilan` | boolean, default `false` | Perwakilan yang bisa daftarkan/tambah anggota & ajukan laporan akhir |

**Unique komposit `(by_spk_id, by_user_id)`**: satu petugas cuma bisa punya satu baris per SPK. Ini bukan cuma index, tapi backstop integritas data yang sesungguhnya — `daftarkanTim()`/`tambahAnggota()` cuma mengecek baris yang sudah ada lewat PHP sebelum insert, yang secara teori punya celah race condition antara dua request bersamaan; constraint inilah yang benar-benar mencegah baris duplikat, apapun yang terlewat di pengecekan level aplikasi.

## `laporan_pengerjaan`

Laporan hasil kerja petugas, per `rambu_pasang`.

| Kolom | Tipe | Keterangan |
|---|---|---|
| `rambu_pasang_id` | FK &rarr; `rambu_pasang`, **cascade**, indexed | |
| `dilaporkan_oleh` | FK &rarr; `users`, **restrict** | |
| `foto_sesudah` | string, nullable | |
| `koordinat_gps` | string, nullable | |
| `catatan_lapangan` | string, nullable | |
| `status` | string, default `diajukan` | `diajukan`/`diterima`/`ditolak`, enum `StatusLaporan` |
| `catatan_penolakan` | string, nullable | Wajib diisi admin kalau menolak |
| `divalidasi_oleh` | FK &rarr; `users`, nullable, **restrict** | |
| `divalidasi_pada` | timestamp, nullable | |

**Relasi**: `belongsTo` &rarr; `rambu_pasang`, `users` (pelapor & validator); `hasMany` &rarr; `barang_bahan`.

Satu `rambu_pasang` bisa punya **lebih dari satu** `laporan_pengerjaan` sepanjang waktu (kalau laporan pertama ditolak lalu direvisi dan diajukan ulang). Riwayat penolakan tidak ditimpa, tapi jadi baris baru.

Baris `laporan_pengerjaan` yang masih aktif (belum digantikan revisi) bisa diedit di tempat selama SPK-nya belum mengajukan Laporan Akhir, lewat form Laporan Pengerjaan yang sama.

## `barang_bahan`

Daftar barang/bahan yang dipakai dalam satu laporan pengerjaan. Hanya `created_at`.

| Kolom | Tipe |
|---|---|
| `laporan_pengerjaan_id` | FK &rarr; `laporan_pengerjaan`, **cascade** |
| `nama` | string |
| `jumlah` | unsigned int |
| `satuan` | string |

## `kendala`

Laporan kendala lapangan per `rambu_pasang`.

| Kolom | Tipe | Keterangan |
|---|---|---|
| `rambu_pasang_id` | FK &rarr; `rambu_pasang`, **cascade**, indexed | |
| `dilaporkan_oleh` | FK &rarr; `users`, **restrict** | |
| `alasan` | string, **wajib diisi** | |
| `foto` | string, nullable | |

## `laporan_kondisi`

Temuan kondisi rambu rusak, independen dari SPK aktif manapun.

| Kolom | Tipe | Keterangan |
|---|---|---|
| `rambu_id` | FK &rarr; `rambu`, **restrict** | |
| `dilaporkan_oleh` | FK &rarr; `users`, **restrict** | |
| `kondisi_dilaporkan` | string | |
| `foto` | string, nullable | |
| `catatan` | string, nullable | |
| `status_tindak_lanjut` | string, default `baru` | `baru`/`sudah_dibuatkan_spk`/`ditolak`, enum `StatusTindakLanjut` |
| `ditindaklanjuti_oleh` | FK &rarr; `users`, nullable, **restrict** | |

## `rt_perwakilan`

Kontak RT/perwakilan warga per SPK, untuk tanda tangan manual di kertas. Hanya `created_at`.

| Kolom | Tipe |
|---|---|
| `nama_lengkap` | string |
| `no_telepon` | string, nullable |
| `rtperwakilan_spk_id` | FK &rarr; `spk`, **cascade** |

## `audit_log`

Jejak aksi bisnis kunci. Hanya `created_at`, append-only, tidak ada `updated_at`.

| Kolom | Tipe | Keterangan |
|---|---|---|
| `user_id` | FK &rarr; `users`, **restrict** | Siapa yang melakukan aksi |
| `spk_id` | FK &rarr; `spk`, nullable, **set null**, indexed | Kalau SPK-nya dihapus (skenario non-aplikasi), log tidak ikut hilang, cuma referensinya di-null-kan |
| `aksi` | string | Mis. `spk_dibuat`, `laporan_dikirim`, `validasi_diterima`, `spk_dibatalkan`, `spk_diedit`, `rambu_pasang_dibatalkan`, `rambu_pasang_dihapus`, `temuan_ditolak`, `deadline_disesuaikan` (otomatis lewat `PenyesuaianDeadlineSpk`), `deadline_diperpanjang` (manual oleh admin saat menolak validasi, lihat [ALUR-BISNIS.md](ALUR-BISNIS.md)) |
| `tabel_terkait`, `record_id_terkait` | nullable | Belum dipakai secara konsisten di kode saat ini |
| `keterangan` | string, nullable | Deskripsi singkat aksi |

## `notifikasi`

Notifikasi in-app per user. Hanya `created_at`.

| Kolom | Tipe | Keterangan |
|---|---|---|
| `user_id` | FK &rarr; `users`, **restrict**, indexed | Query paling sering di seluruh sistem (badge notifikasi belum-dibaca di header, tiap halaman) |
| `judul` | string | |
| `pesan` | string | |
| `url` | string, nullable | Link tujuan kalau notifikasi ini punya halaman terkait yang relevan (kartu notifikasinya jadi bisa diklik langsung). Sebagian notifikasi (mis. temuan ditolak) sengaja tidak punya `url` kalau memang tidak ada halaman yang pas dituju |
| `foto` | string, nullable | Path foto yang relevan saat notifikasi ini dibuat (mis. foto sesudah/kendala saat laporan diterima/ditolak, foto temuan), dibekukan sesuai kondisi saat itu. Dipakai buat melampirkan foto di pesan Telegram, bukan ditampilkan di halaman Notifikasi in-app |
| `dibaca` | boolean, default `false` | |

Setiap baris `notifikasi` yang dibuat untuk user yang sudah menghubungkan Telegram-nya otomatis memicu pengiriman pesan yang sama lewat bot (lewat `NotifikasiObserver`), tanpa perlu ubah kode di titik-titik yang membuat notifikasi.

## `system_error_log`

Exception tak terduga yang tertangkap otomatis oleh exception handler global (`bootstrap/app.php`). Hanya `created_at`.

| Kolom | Tipe | Keterangan |
|---|---|---|
| `level` | string | `info`/`warning`/`error`/`critical`, enum `ErrorLevel` |
| `pesan` | string | |
| `detail` | text, nullable | Stack trace lengkap |
| `endpoint` | string, nullable | URL yang diakses saat error terjadi |
| `user_id` | FK &rarr; `users`, nullable, **restrict** | |

Hanya exception "beneran" yang tercatat: validasi gagal, redirect auth, 404, dan HTTP error di bawah 500 sengaja **tidak** dicatat di sini (bukan bug, memang bukan "error sistem").

---

## Enum

Semua kolom status/jenis disimpan sebagai string di database, tapi di-cast ke [PHP backed enum](../app/Enums) di level model, jadi di kode selalu berupa objek enum, bukan string mentah.

| Enum | Nilai |
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
| `AsalPermintaan` | `internal`, `laporan_masyarakat`, `instruksi_pemerintah`, `evaluasi_petugas` |
| `ErrorLevel` | `info`, `warning`, `error`, `critical` |
| `BentukIkon` | `bulat`, `kotak` |

## Trait `ComposesWilayah`

Dipakai oleh model `Spk` dan `Rambu` (`app/Concerns/ComposesWilayah.php`). Lewat event `saving`, kalau kolom `wilayah` kosong DAN salah satu dari `jalan`/`rt`/`kelurahan` terisi, `wilayah` otomatis disusun jadi teks seperti `"Jl. {jalan} RT. {rt} Kel. {kelurahan}"`. Ini memungkinkan form Buat/Edit SPK memakai field terstruktur tanpa perlu mengubah ~28 tempat di kode lain yang masih membaca/mencari/`groupBy` kolom `wilayah` sebagai teks polos.
