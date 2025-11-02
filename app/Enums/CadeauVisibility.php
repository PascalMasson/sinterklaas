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
    public function toHumanReadable(?string $recipientName = null): string
    {
        $label = self::LABELS[$this->value];

        if ($this === self::HIDDEN && $recipientName !== null && $recipientName !== '') {
            return str_replace('ontvanger', $recipientName, $label);
        }

        return $label;
    }

    /**
     * Helper for form/select components requiring value => label arrays.
     */
    public static function options(?string $recipientName = null): array
    {
        $options = [];

        foreach (self::cases() as $visibility) {
            $options[$visibility->value] = $visibility->toHumanReadable($recipientName);
        }

        return $options;
    }

    /**
     * Normalises a value that may already be Dutch or legacy English.
     */
    public static function fromLocalized(string $value): self
    {
        $normalized = Str::of($value)->upper()->trim();
        $normalizedString = (string) $normalized;

        if (in_array($normalizedString, ['PUBLIC', 'PUBLIEK', 'IEDEREEN'], true)) {
            return self::PUBLIC;
        }

        if (
            in_array($normalizedString, ['HIDDEN', 'VERBORGEN', 'IEDEREEN BEHALVE ONTVANGER'], true)
            || $normalized->startsWith('IEDEREEN BEHALVE ')
        ) {
            return self::HIDDEN;
        }

        if (in_array($normalizedString, ['PRIVATE', 'PRIVE', 'PRIVÉ', 'ALLEEN IK'], true)) {
            return self::PRIVATE;
        }

        return self::PUBLIC;
    }
}
