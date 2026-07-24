<?php

namespace App\Enums;

enum StatusTindakLanjut: string
{
    case Baru = 'baru';
    case SudahDibuatkanSpk = 'sudah_dibuatkan_spk';
    case Ditolak = 'ditolak';

    public function label(): string
    {
        return match ($this) {
            self::Baru => 'Baru',
            self::SudahDibuatkanSpk => 'Sudah Dibuatkan SPK',
            self::Ditolak => 'Ditolak',
        };
    }
}
