<?php

namespace App\Enums;

use Illuminate\Support\Str;

enum CadeauVisibility: string
{
    case PUBLIC = 'public';
    case HIDDEN = 'hidden';
    case PRIVATE = 'private';

    private const LABELS = [
        self::PUBLIC->value => 'Iedereen',
        self::HIDDEN->value => 'Iedereen behalve ontvanger',
        self::PRIVATE->value => 'Alleen ik',
    ];

    /**
     * Returns the Dutch label for the visibility state.
     */
    public function toHumanReadable(): string
    {
        return self::LABELS[$this->value];
    }

    /**
     * Helper for form/select components requiring value => label arrays.
     */
    public static function options(): array
    {
        return self::LABELS;
    }

    /**
     * Normalises a value that may already be Dutch or legacy English.
     */
    public static function fromLocalized(string $value): self
    {
        $normalized = Str::of($value)->upper()->trim();

        return match ($normalized) {
            'PUBLIC', 'PUBLIEK', 'IEDEREEN' => self::PUBLIC,
            'HIDDEN', 'VERBORGEN', 'IEDEREEN BEHALVE ONTVANGER' => self::HIDDEN,
            'PRIVATE', 'PRIVE', 'PRIVÉ', 'ALLEEN IK' => self::PRIVATE,
            default => self::PUBLIC,
        };
    }
}
