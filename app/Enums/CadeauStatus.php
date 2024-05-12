<?php

namespace App\Enums;

enum CadeauStatus: string
{
    case AVAILABLE = 'available';
    case RESERVED = 'reserved';
    case PURCHASED = 'purchased';

    public function toHumanReadable(): string
    {
        return match ($this) {
            self::AVAILABLE => 'Vrij',
            self::RESERVED => 'Gereserveerd',
            self::PURCHASED => 'Gekocht',
        };
    }
}
