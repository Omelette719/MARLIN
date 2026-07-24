<?php

namespace App\Enums;

enum StatusLaporan: string
{
    case Diajukan = 'diajukan';
    case Diterima = 'diterima';
    case Ditolak = 'ditolak';

    public function label(): string
    {
        return match ($this) {
            self::Diajukan => 'Diajukan',
            self::Diterima => 'Diterima',
            self::Ditolak => 'Ditolak',
        };
    }
}
