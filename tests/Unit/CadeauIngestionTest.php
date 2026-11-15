<?php

namespace Tests\Unit;

use App\Domain\Cadeaus\CadeauIngestion;
use App\Domain\Cadeaus\CadeauPersistence;
use App\Enums\CadeauStatus;
use App\Enums\CadeauVisibility;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class CadeauIngestionTest extends TestCase
{
    private CadeauIngestion $ingestion;

    private object $persistence;

    protected function setUp(): void
    {
        parent::setUp();

        $this->persistence = new class implements CadeauPersistence
        {
            /** @var list<array<string, mixed>> */
            public array $cadeaus = [];

            public function replace(array $cadeaus): void
            {
                $this->cadeaus = $cadeaus;
            }
        };
        $this->ingestion = new CadeauIngestion($this->persistence);
    }

    public function test_it_canonicalizes_an_external_cadeau_row(): void
    {
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
        ], $this->ingestion->canonicalize([
            'title' => 'Warme sokken',
            'description' => 'Voor koude avonden',
            'status' => 'GERESERVEERD',
            'price' => '12,50',
            'location' => 'https://example.test/sokken',
            'visibility' => 'IEDEREEN BEHALVE ONTVANGER',
            'created_by_user_id' => 2,
            'list_user_id' => 4,
            'reserved_by_user_id' => 6,
        ]));
    }

    public function test_it_canonicalizes_and_persists_a_replacement_batch(): void
    {
        $this->ingestion->replace([
            ['title' => 'Warme sokken', 'status' => 'VRIJ', 'location' => 'Winkel om de hoek'],
        ]);

        self::assertSame([
            [
                'title' => 'Warme sokken',
                'status' => CadeauStatus::AVAILABLE,
                'reserved_by_user_id' => null,
                'location_other' => 'Winkel om de hoek',
                'location_type' => 'overig',
            ],
        ], $this->persistence->cadeaus);
    }

    public function test_it_rejects_unknown_statuses_before_persistence(): void
    {
        $this->expectException(InvalidArgumentException::class);

        try {
            $this->ingestion->replace([['status' => 'ONBEKEND']]);
        } finally {
            self::assertSame([], $this->persistence->cadeaus);
        }
    }
}
