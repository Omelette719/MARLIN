<?php

namespace App\Enums;

enum BentukIkon: string
{
    case Bulat = 'bulat';
    case Kotak = 'kotak';

    public function label(): string
    {
        return match ($this) {
            self::Bulat => 'Bulat',
            self::Kotak => 'Kotak',
        };
    }
}
