<?php

namespace App\Enums;

enum KondisiRambu: string
{
    case Baik = 'baik';
    case Rusak = 'rusak';

    public function label(): string
    {
        return match ($this) {
            self::Baik => 'Baik',
            self::Rusak => 'Rusak',
        };
    }
}
