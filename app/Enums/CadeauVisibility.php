<?php

namespace App\Enums;

enum CadeauVisibility: string
{
    case PUBLIC = 'public';
    case HIDDEN = 'hidden';
    case PRIVATE = 'private';

    public function toHumanReadable(): string
    {
        return match ($this) {
            self::PUBLIC => 'Publiek',
            self::HIDDEN => 'Verborgen',
            self::PRIVATE => 'Geheim',
        };
    }
}
