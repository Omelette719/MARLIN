<?php

namespace App\Enums;

enum JenisPekerjaan: string
{
    case PasangBaru = 'pasang_baru';
    case Perbaikan = 'perbaikan';

    public function label(): string
    {
        return match ($this) {
            self::PasangBaru => 'Pasang Baru',
            self::Perbaikan => 'Perbaikan',
        };
    }
}
