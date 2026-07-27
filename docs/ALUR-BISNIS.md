# Alur Bisnis Sistem MARLIN

Penjelasan detail siklus hidup SPK dan aturan bisnis di baliknya — untuk memahami *kenapa* sistem berperilaku seperti yang dijelaskan di [FITUR.md](FITUR.md). Diverifikasi langsung dari kode per 2026-07-27.

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
Semua rambu dalam SPK sudah tertunda/menunggu_validasi?
        │
        ▼ Ya — perwakilan bisa "Ajukan Laporan Akhir"
   spk.laporan_akhir_diajukan_at = sekarang
   → SPK masuk antrean Validasi Pengerjaan admin
        │
        ▼
Admin buka Detail Validasi, per rambu centang terima/tidak
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

Bergabung ke tim SPK (`daftarkanTim`) bisa dilakukan siapa saja yang mendaftar sebagai **perwakilan** — perwakilan itu sekaligus mendaftarkan rekan setimnya dalam satu aksi. Tapi **hanya perwakilan** (`dikerjakan_oleh.is_perwakilan = true`) yang bisa:
- Mengisi Form Laporan Pengerjaan
- Mengisi Form Kendala
- Mengajukan Laporan Akhir

Anggota tim non-perwakilan bisa ikut bekerja fisik di lapangan dan melihat detail SPK, tapi tidak mengoperasikan form-form di atas. Ini satu titik akuntabilitas per SPK — jelas siapa yang bertanggung jawab melaporkan.

### Kenapa ada gate "Laporan Akhir" sebelum validasi?

Admin tidak memvalidasi rambu satu-satu begitu petugas submit laporan — validasi dilakukan **per-batch, per-SPK**, setelah SEMUA rambu dalam SPK itu sudah ditangani (baik lewat laporan pengerjaan maupun kendala). Halaman **Validasi Laporan** (daftar) hanya menampilkan SPK yang `laporan_akhir_diajukan_at` sudah terisi — jadi admin tidak perlu bolak-balik memvalidasi rambu yang baru sebagian selesai.

> Catatan teknis: halaman **Detail Validasi** sendiri sebenarnya bisa diakses langsung lewat URL walau `laporan_akhir_diajukan_at` belum diisi (tidak ada pengecekan tambahan di `mount()`), tapi alur normal aplikasi selalu mengarahkan admin lewat halaman daftar yang sudah terfilter, jadi ini bukan celah yang berdampak nyata.

### Kenapa `laporan_akhir_diajukan_at` di-reset ke `null` setiap kali admin validasi?

Supaya kalau ada rambu yang ditolak (`revisi`), SPK itu otomatis "keluar" dari antrean validasi (karena gate-nya di-reset) sampai perwakilan mengajukan ulang laporan akhir setelah merevisi rambu yang ditolak. Ini mencegah admin memvalidasi ulang rambu yang sama berkali-kali tanpa perwakilan benar-benar menandai "sudah saya perbaiki, tolong cek lagi".

### Perbaikan per-rambu, bukan per-SPK

Kalau satu rambu ditolak validasinya, **hanya rambu itu** yang statusnya kembali ke `revisi` — rambu lain dalam SPK yang sama yang sudah `selesai` tidak ikut terganggu. SPK baru berubah jadi `selesai` kalau **benar-benar semua** `rambu_pasang` di dalamnya sudah `selesai` atau `batal`.

### Laporan pengerjaan bisa berlapis

Satu `rambu_pasang` bisa punya lebih dari satu baris `laporan_pengerjaan` seiring waktu — kalau laporan pertama ditolak, laporan revisi berikutnya jadi baris BARU (bukan menimpa yang lama), jadi riwayat penolakan tetap tersimpan lengkap.

## Temuan Kondisi (Independen dari SPK Aktif)

Petugas bisa melaporkan rambu yang ditemukan rusak **kapan saja**, tidak terikat pada SPK aktif mereka (mis. ditemukan saat patroli, bukan sedang mengerjakan SPK apapun) — dan **bisa dilakukan siapa saja**, bukan cuma perwakilan (beda dengan Kendala/Laporan yang perwakilan-only).

Begitu dilaporkan:
1. `rambu.kondisi_terkini` langsung berubah jadi `rusak` — supaya pin di peta segera berubah warna, tanpa menunggu admin memproses apapun.
2. Semua admin dapat notifikasi.
3. Laporan masuk ke antrean **Temuan Lapangan** admin dengan `status_tindak_lanjut = baru`.
4. Admin bisa membuat SPK perbaikan baru yang merujuk ke `laporan_kondisi_id` ini — begitu SPK dibuat, `status_tindak_lanjut` berubah jadi `sudah_dibuatkan_spk`.

## Urgensi Otomatis

`urgensi` pada SPK **selalu dihitung otomatis** (admin tidak bisa set manual), berdasarkan sisa hari ke `deadline`:

| Kondisi | Urgensi |
|---|---|
| Ditandai **Prioritas** | Tinggi (terlepas dari deadline) |
| Sisa ≤ 2 hari | Tinggi |
| Sisa ≤ 7 hari | Sedang |
| Selebihnya | Rendah |

Dihitung ulang setiap kali SPK dibuat atau di-edit (kalau deadline/prioritas berubah).

## Warna Pin di Peta — Urutan Prioritas

Warna dihitung di sisi client (JavaScript), **bukan** kolom tersimpan di database — supaya selalu real-time tanpa perlu query tambahan. Urutan pengecekan (yang pertama cocok, itu yang dipakai):

1. **Cyan** — `rambu_pasang.status = menunggu_validasi` (menang atas segalanya, termasuk prioritas/urgent — laporan sudah dikirim, giliran admin, tidak perlu terus terlihat "darurat")
2. **Merah** — `status = urgent`, atau SPK-nya `prioritas = true`, atau `urgensi = tinggi`
3. **Kuning** — `kondisi_terkini = rusak`, atau sedang `perbaikan` yang belum `selesai`
4. **Biru tua** — `status = selesai` (atau tidak ada tugas aktif) DAN `kondisi_terkini = baik`
5. **Abu-abu** — selain semua di atas (belum dikerjakan)

> Urutan ini **sengaja berbeda** dari draft spesifikasi awal proyek (yang menyebut merah selalu menang atas semua warna lain) — diputuskan lewat diskusi bahwa status "menunggu validasi" harus tetap terlihat progress-nya di peta, bukan tertutup warna merah selama menunggu giliran admin.

## Pembatalan SPK

Admin bisa membatalkan SPK yang masih **aktif** (tombol Batalkan SPK). Efeknya:
1. `spk.status = dibatalkan` — **tidak pernah** `DELETE` baris SPK.
2. Semua `rambu_pasang` yang belum `selesai`/`batal` ditandai `batal`.
3. Rambu `pasang_baru` yang belum pernah benar-benar terpasang (`sudah_terpasang = false`) otomatis hilang dari peta — karena tidak pernah jadi objek fisik nyata.
4. Rambu `perbaikan` tetap tampil di peta apa adanya — `kondisi_terkini` tetap `rusak`, menunggu SPK perbaikan lain di masa depan.
