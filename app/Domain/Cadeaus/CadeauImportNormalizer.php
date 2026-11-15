<?php

namespace App\Domain\Cadeaus;

use App\Enums\CadeauStatus;
use App\Enums\CadeauVisibility;
use InvalidArgumentException;

final class CadeauImportNormalizer
{
    /**
     * @param  array<string, mixed>  $source
     * @return array<string, mixed>
     */
    public function normalize(array $source): array
    {
        $attributes = [];

        foreach ([
            'title',
            'description',
            'created_by_user_id',
            'list_user_id',
            'reserved_by_user_id',
        ] as $attribute) {
            if (array_key_exists($attribute, $source)) {
                $attributes[$attribute] = $source[$attribute];
            }
        }

        if (array_key_exists('status', $source)) {
            $attributes['status'] = $this->normalizeStatus($source['status']);
        }

        if (array_key_exists('price', $source)) {
            $attributes['price'] = $this->normalizePrice($source['price']);
        }

        if (array_key_exists('location', $source)) {
            $attributes = [...$attributes, ...$this->normalizeLocation($source['location'])];
        }

        if (array_key_exists('visibility', $source)) {
            $attributes['visibility'] = $this->normalizeVisibility($source['visibility']);
        }

        return $attributes;
    }

    private function normalizeStatus(mixed $value): CadeauStatus
    {
        if ($value instanceof CadeauStatus) {
            return $value;
        }

        return match (strtoupper(trim((string) $value))) {
            'AVAILABLE', 'VRIJ' => CadeauStatus::AVAILABLE,
            'RESERVED', 'GERESERVEERD' => CadeauStatus::RESERVED,
            'PURCHASED', 'GEKOCHT' => CadeauStatus::PURCHASED,
            default => throw new InvalidArgumentException("Unknown cadeau status: {$value}"),
        };
    }

    private function normalizePrice(mixed $value): ?string
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        return number_format((float) str_replace(',', '.', (string) $value), 2, '.', '');
    }

    /**
     * @return array{location_url?: string, location_other?: string, location_type: string}
     */
    private function normalizeLocation(mixed $value): array
    {
        $location = (string) $value;

        if (filter_var($location, FILTER_VALIDATE_URL)) {
            return [
                'location_url' => $location,
                'location_type' => 'website',
            ];
        }

        return [
            'location_other' => $location,
            'location_type' => 'overig',
        ];
    }

    private function normalizeVisibility(mixed $value): CadeauVisibility
    {
        if ($value instanceof CadeauVisibility) {
            return $value;
        }

        return CadeauVisibility::fromLocalized((string) $value);
    }
}
