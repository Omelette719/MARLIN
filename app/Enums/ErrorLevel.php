<?php

namespace App\Enums;

enum ErrorLevel: string
{
    case Info = 'info';
    case Warning = 'warning';
    case Error = 'error';
    case Critical = 'critical';

    public function label(): string
    {
        return match ($this) {
            self::Info => 'Info',
            self::Warning => 'Warning',
            self::Error => 'Error',
            self::Critical => 'Critical',
        };
    }
}
