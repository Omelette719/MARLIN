<?php

namespace App\Support;

/**
 * Kota Banjarmasin's 5 kecamatan and their 52 kelurahan, per "Luas Wilayah
 * Kelurahan Menurut Kecamatan di Kota Banjarmasin" (Sekretariat Daerah -
 * Bagian Pemerintahan, 2024). Kecamatan is deliberately not a stored column
 * anywhere (Spk/Rambu only ever capture kelurahan) — it's derived from this
 * fixed list whenever it's needed, e.g. for the Peta filter.
 */
class WilayahBanjarmasin
{
    public const KECAMATAN = [
        'Banjarmasin Selatan' => [
            'Mantuil', 'Kelayan Selatan', 'Pekauman', 'Kelayan Barat', 'Kelayan Tengah',
            'Kelayan Dalam', 'Murung Raya', 'Kelayan Timur', 'Tanjung Pagar', 'Pemurus Dalam',
            'Pemurus Baru', 'Basirih Selatan',
        ],
        'Banjarmasin Timur' => [
            'Kuripan', 'Kebun Bunga', 'Karang Mekar', 'Sungai Bilu', 'Sungai Lulut',
            'Banua Anyar', 'Pengambangan', 'Pekapuran Raya', 'Pemurus Luar',
        ],
        'Banjarmasin Barat' => [
            'Belitung Utara', 'Belitung Selatan', 'Pelambuan', 'Telaga Biru', 'Telawang',
            'Teluk Tiram', 'Kuin Selatan', 'Kuin Cerucuk', 'Basirih',
        ],
        'Banjarmasin Utara' => [
            'Alalak Tengah', 'Alalak Utara', 'Alalak Selatan', 'Sungai Jingah', 'Sungai Miai',
            'Surgi Mufti', 'Pangeran', 'Antasan Kecil Timur', 'Kuin Utara', 'Sungai Andai',
        ],
        'Banjarmasin Tengah' => [
            'Kertak Baru Ilir', 'Kertak Baru Ulu', 'Mawar', 'Teluk Dalam', 'Antasan Besar',
            'Pasar Lama', 'Seberang Mesjid', 'Gadang', 'Melayu', 'Sungai Baru',
            'Pekapuran Laut', 'Kelayan Luar',
        ],
    ];

    public static function kecamatanOptions(): array
    {
        return array_keys(self::KECAMATAN);
    }

    /**
     * All 52 kelurahan, alphabetized — the kecamatan grouping only matters
     * for the kecamatan filter/lookup below, not for how this flat list
     * itself is presented (e.g. in the Buat/Edit Surat dropdown).
     */
    public static function kelurahanOptions(): array
    {
        return collect(self::KECAMATAN)->flatten()->sort()->values()->all();
    }

    public static function kelurahanByKecamatan(string $kecamatan): array
    {
        return self::KECAMATAN[$kecamatan] ?? [];
    }

    public static function kecamatanFromKelurahan(?string $kelurahan): ?string
    {
        if (blank($kelurahan)) {
            return null;
        }

        foreach (self::KECAMATAN as $kecamatan => $daftarKelurahan) {
            if (in_array($kelurahan, $daftarKelurahan, true)) {
                return $kecamatan;
            }
        }

        return null;
    }
}
