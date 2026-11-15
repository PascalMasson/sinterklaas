<?php

namespace Tests\Unit;

use App\Domain\Cadeaus\CadeauImportNormalizer;
use App\Enums\CadeauStatus;
use App\Enums\CadeauVisibility;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class CadeauImportNormalizerTest extends TestCase
{
    public function test_it_normalizes_a_cadeau_import_row(): void
    {
        $attributes = (new CadeauImportNormalizer)->normalize([
            'title' => 'Warme sokken',
            'description' => 'Voor koude avonden',
            'status' => 'GERESERVEERD',
            'price' => '12,50',
            'location' => 'https://example.test/sokken',
            'visibility' => 'IEDEREEN BEHALVE ONTVANGER',
            'created_by_user_id' => 2,
            'list_user_id' => 4,
            'reserved_by_user_id' => 6,
        ]);

        self::assertSame([
            'title' => 'Warme sokken',
            'description' => 'Voor koude avonden',
            'created_by_user_id' => 2,
            'list_user_id' => 4,
            'reserved_by_user_id' => 6,
            'status' => CadeauStatus::RESERVED,
            'price' => '12.50',
            'location_url' => 'https://example.test/sokken',
            'location_type' => 'website',
            'visibility' => CadeauVisibility::HIDDEN,
        ], $attributes);
    }

    public function test_it_normalizes_a_non_url_location(): void
    {
        self::assertSame([
            'location_other' => 'Winkel om de hoek',
            'location_type' => 'overig',
        ], (new CadeauImportNormalizer)->normalize([
            'location' => 'Winkel om de hoek',
        ]));
    }

    public function test_it_rejects_unknown_statuses(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new CadeauImportNormalizer)->normalize(['status' => 'ONBEKEND']);
    }
}
