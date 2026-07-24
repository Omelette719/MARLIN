<?php

namespace App\Enums;

enum StatusSpk: string
{
    case Aktif = 'aktif';
    case Selesai = 'selesai';
    case Dibatalkan = 'dibatalkan';

    public function label(): string
    {
        return match ($this) {
            self::Aktif => 'Aktif',
            self::Selesai => 'Selesai',
            self::Dibatalkan => 'Dibatalkan',
        };
    }
}
