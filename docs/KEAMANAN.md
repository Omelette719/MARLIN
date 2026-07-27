# Keamanan Sistem MARLIN

Dokumen ini merinci langkah-langkah keamanan yang sudah diterapkan, serta batasan/keputusan desain yang sengaja diambil. Semua poin di sini diverifikasi langsung dari kode (bukan asumsi) per 2026-07-27.

---

## Daftar Isi

- [Autentikasi](#autentikasi)
- [Otorisasi & RBAC](#otorisasi--rbac)
- [Proteksi Data & Input](#proteksi-data--input)
- [Upload File](#upload-file)
- [Audit Trail](#audit-trail)
- [Keputusan Desain yang Disengaja](#keputusan-desain-yang-disengaja-bukan-celah)
- [Batasan yang Diketahui](#batasan-yang-diketahui)

---

## Autentikasi

**Login berbasis NIP**, bukan email (`config/fortify.php` &rarr; `'username' => 'nip'`). Kredensial diverifikasi lewat callback custom di `app/Providers/FortifyServiceProvider.php`, bukan default Fortify — ini yang memungkinkan pengecekan status aktif akun (lihat di bawah).

**Hash password**: bcrypt, cost factor 12 (`BCRYPT_ROUNDS=12` di `.env`). Kolom `password` di-cast `'hashed'` di model `User`, jadi setiap kali di-assign otomatis di-hash — tidak ada jalur kode yang menyimpan password mentah.

**Two-Factor Authentication (2FA)**: tersedia dan wajib dikonfirmasi ulang dengan password sebelum bisa diaktifkan/diubah (`confirmPassword: true` di `config/fortify.php`). Dikelola dari Settings &rarr; Keamanan.

**Rate limiting**:
- Login: **5 percobaan/menit**, dikunci per kombinasi NIP+alamat IP (`app/Providers/FortifyServiceProvider.php`).
- Verifikasi kode 2FA: **5 percobaan/menit** per sesi login yang sedang berjalan.
- Reset password mandiri (modal di halaman login): **5 percobaan/menit** per NIP+IP juga.

**Nonaktifkan akun**: admin bisa menonaktifkan akun petugas (`aktif = false`). Login akan ditolak dengan pesan jelas ("Akun ini telah dinonaktifkan..."). 

> ⚠️ **Catatan**: pengecekan `aktif` hanya terjadi **saat login**. Kalau sebuah akun dinonaktifkan SAAT penggunanya sedang login (sesi masih aktif), sesi itu tidak otomatis terputus — pengguna baru benar-benar terblokir saat mencoba login ulang. Ini sesuai perilaku default Laravel session (server-side session, bukan token yang divalidasi tiap request) dan wajar untuk skala aplikasi ini, tapi baik untuk diketahui kalau suatu saat butuh "force logout" instan.

**Registrasi**: sengaja dinonaktifkan (fitur Fortify untuk registrasi mandiri tidak diaktifkan). Semua akun dibuat terpusat oleh admin lewat Manajemen Petugas — tidak ada halaman "daftar akun baru" yang bisa diakses publik.

## Otorisasi & RBAC

Middleware `role:admin` / `role:user` (`app/Http/Middleware/EnsureUserHasRole.php`) menjaga tiap grup route sesuai peran. Kalau ketahuan salah peran, pengguna di-redirect ke dashboard sesuai perannya sendiri (bukan menampilkan halaman error) — jadi percobaan mengakses halaman peran lain terasa seperti "halaman itu tidak ada", bukan seperti "kamu ditolak", yang sedikit mengurangi informasi yang bisa dipakai untuk mengintai struktur sistem.

**Konfirmasi ulang password** untuk aksi sensitif: halaman Settings &rarr; Keamanan (ganti password, kelola 2FA) mewajibkan konfirmasi ulang password dulu (`password.confirm` middleware) sebelum bisa diakses, walau sesi login masih berjalan.

**Mass assignment**: seluruh model (14 model) memakai atribut PHP `#[Fillable([...])]` yang eksplisit menyebutkan kolom mana yang boleh diisi lewat `create()`/`update()` — tidak ada model yang memakai `$guarded = []` (yang berarti "semua kolom boleh diisi", rawan disalahgunakan lewat input form yang dimanipulasi).

## Proteksi Data & Input

- **CSRF**: semua form HTML biasa (login, 2FA, konfirmasi password, dll) memakai `@csrf`. Form yang dikelola Livewire (`wire:submit`) punya proteksi CSRF bawaan dari Livewire sendiri, jadi token terpisah tidak diperlukan.
- **XSS**: hampir seluruh output di Blade memakai `{{ }}` (otomatis di-escape). Hanya **satu** tempat di seluruh sistem yang memakai output mentah `{!! !!}` — kode QR untuk setup 2FA, yang berasal dari SVG buatan server sendiri (bukan input pengguna), jadi bukan celah XSS.
- **SQL Injection**: seluruh query memakai Eloquent (parameterized query otomatis). Ada 2 penggunaan `selectRaw`/`orderByRaw` di sistem (untuk agregasi dashboard dan pengurutan kondisi rusak-duluan), keduanya memakai string SQL tetap/statis — tidak ada input pengguna yang digabung langsung ke query SQL.
- **Session**: driver `database`, masa berlaku 120 menit, cookie `HttpOnly` + `SameSite=Lax` (default Laravel). Enkripsi cookie sesi tidak diaktifkan (`SESSION_ENCRYPT=false`) — standar untuk aplikasi internal di jaringan terpercaya, tapi kalau nanti sistem ini diakses lewat internet publik tanpa HTTPS, pertimbangkan mengaktifkan `SESSION_SECURE_COOKIE` di server produksi.

## Upload File

Semua upload dibatasi ukuran **maksimal 5 MB** dan divalidasi tipe filenya:

| Jenis Upload | Aturan | Dipakai di |
|---|---|---|
| Foto (survei, sesudah, kendala, temuan) | `image`, maks 5 MB | Buat/Edit SPK, Laporan Pengerjaan, Kendala, Temuan Kondisi |
| Gambar referensi jenis rambu | `image`, maks 5 MB | Kelola Jenis Rambu |
| File referensi SPK | `file` (bebas tipe), maks 5 MB | Buat/Edit SPK — dipakai untuk scan surat, bisa PDF |

Semua file tersimpan di disk `public` (`storage/app/public`, diekspos lewat symlink `public/storage`). **File yang sudah terupload bisa diakses siapa saja yang tahu URL-nya, tanpa perlu login** — ini standar untuk file statis di banyak aplikasi Laravel skala ini, tapi berarti URL foto/dokumen jangan sampai bocor ke pihak yang tidak seharusnya (URL-nya sendiri berisi nama file acak dari `Storage::store()`, jadi tidak mudah ditebak, tapi tetap bukan proteksi akses yang sesungguhnya).

## Audit Trail

Tabel `audit_log` mencatat aksi bisnis kunci (SPK dibuat/diedit/dibatalkan, laporan dikirim/diterima/ditolak, kendala diajukan, dll) — bersifat **append-only** (tidak ada kolom `updated_at`, jadi catatan tidak bisa diubah setelah dibuat lewat jalur normal aplikasi).

- **Admin**: lihat seluruh riwayat aktivitas di sistem.
- **Petugas**: hanya lihat aktivitas yang **dia lakukan sendiri** (`user_id` miliknya), bukan aktivitas admin atau rekan setim lain — walau di SPK yang sama.

## Keputusan Desain yang Disengaja (Bukan Celah)

### Petugas bisa buka Detail SPK apapun (selama masih aktif), bukan cuma yang diikuti

Halaman `/spk/{spk}` (petugas) sengaja **tidak** dibatasi hanya untuk anggota tim SPK tersebut. Ini karena halaman "Daftar Surat Aktif" (dashboard petugas) menampilkan **semua** SPK aktif supaya petugas bisa menemukan & memilih pekerjaan baru — dan tombol "Lihat Detail" di situ mengarah ke halaman yang sama. Kalau dibatasi hanya untuk anggota tim, petugas tidak akan pernah bisa melihat SPK baru untuk memutuskan bergabung — alur inti fitur "gabung tim" jadi rusak.

Aksi yang benar-benar mengubah data (`daftarkanTim`, `tambahAnggota`, `ajukanLaporanAkhir`) tetap punya pengecekan keanggotaan sendiri di dalam kode — jadi yang terbuka cuma **melihat**, bukan **mengubah**.

Sebagai perbandingan yang disengaja berbeda: **unduh PDF Surat Pengantar** (`/spk/{spk}/surat-pengantar`) DIBATASI hanya untuk admin atau petugas yang sudah terdaftar di tim SPK tersebut (lihat `app/Http/Controllers/SuratPengantarController.php`). Ini karena surat pengantar adalah dokumen kerja resmi yang cuma relevan untuk yang benar-benar mengerjakannya, beda konteks dengan "melihat-lihat untuk memutuskan gabung".

## Batasan yang Diketahui

Hal-hal berikut BUKAN kesalahan, tapi trade-off yang masuk akal untuk skala & konteks aplikasi ini (internal, dipakai oleh staf Dishub dan tim lapangan yang jumlahnya terbatas):

1. **Tidak ada "force logout" instan** ketika akun dinonaktifkan — sesi yang sedang berjalan tetap jalan sampai logout manual atau sesi kedaluwarsa (120 menit).
2. **File upload tidak divalidasi per-akses** — begitu ter-upload, URL-nya bisa diakses tanpa login (walau nama filenya acak, bukan bisa ditebak).
3. **Reset password mandiri tidak mengecek status `aktif`** — akun yang dinonaktifkan tetap bisa mengganti passwordnya sendiri lewat modal reset di halaman login (tapi tetap tidak akan bisa login setelahnya, karena pengecekan `aktif` ada di jalur login, bukan di jalur ganti-password).
4. **`SESSION_ENCRYPT=false`** dan tidak ada `SESSION_SECURE_COOKIE` eksplisit di `.env.example` — kalau nanti deploy ke server produksi yang diakses lewat internet publik, aktifkan HTTPS dan pertimbangkan set `SESSION_SECURE_COOKIE=true`.
