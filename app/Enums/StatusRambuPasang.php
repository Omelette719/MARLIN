<?php

namespace App\Enums;

enum StatusRambuPasang: string
{
    case Belum = 'belum';
    case Urgent = 'urgent';
    case Tertunda = 'tertunda';
    case MenungguValidasi = 'menunggu_validasi';
    case Revisi = 'revisi';
    case Selesai = 'selesai';
    case Batal = 'batal';

    public function label(): string
    {
        return match ($this) {
            self::Belum => 'Belum',
            self::Urgent => 'Urgent',
            self::Tertunda => 'Tertunda',
            self::MenungguValidasi => 'Menunggu Validasi',
            self::Revisi => 'Revisi',
            self::Selesai => 'Selesai',
            self::Batal => 'Batal',
        };
    }
}
