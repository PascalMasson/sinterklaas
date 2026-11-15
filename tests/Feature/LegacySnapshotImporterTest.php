<?php

namespace Tests\Feature;

use App\Domain\Cadeaus\CadeauIngestion;
use App\Domain\LegacySnapshots\LegacySnapshotImporter;
use App\Infrastructure\Cadeaus\EloquentCadeauPersistence;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use Tests\TestCase;

class LegacySnapshotImporterTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');

        Schema::create('foppers', function (Blueprint $table): void {
            $table->id();
            $table->text('inhoud');
            $table->unsignedBigInteger('created_by_user_id');
            $table->unsignedBigInteger('created_for_user_id');
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('cadeaus', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->longText('description')->nullable();
            $table->string('status');
            $table->decimal('price')->nullable();
            $table->string('location_type')->nullable();
            $table->string('location_url')->nullable();
            $table->string('location_other')->nullable();
            $table->string('visibility')->nullable();
            $table->unsignedBigInteger('created_by_user_id')->nullable();
            $table->unsignedBigInteger('list_user_id')->nullable();
            $table->unsignedBigInteger('reserved_by_user_id')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('media', function (Blueprint $table): void {
            $table->id();
        });
    }

    public function test_it_replaces_cadeaus_and_foppers_from_a_legacy_snapshot(): void
    {
        $this->importer()->import([
            ['type' => 'table', 'name' => 'foppers', 'data' => [
                ['description' => 'Een gekke fop', 'fopperVan' => 1, 'fopperVoor' => 2],
            ]],
            ['type' => 'table', 'name' => 'cadeaus', 'data' => [
                [
                    'title' => 'Warme sokken',
                    'status' => 'GERESERVEERD',
                    'prijs' => '12,50',
                    'location' => 'https://example.test/sokken',
                    'createdBy' => 2,
                    'listId' => 4,
                    'reservedBy' => 6,
                ],
                [
                    'title' => 'Vrij cadeau',
                    'status' => 'VRIJ',
                    'createdBy' => 2,
                    'listId' => 4,
                    'reservedBy' => 99,
                ],
            ]],
        ]);

        self::assertSame('Een gekke fop', DB::table('foppers')->value('inhoud'));
        $cadeau = DB::table('cadeaus')->first(['title', 'status', 'price', 'location_url', 'reserved_by_user_id']);

        self::assertSame('Warme sokken', $cadeau->title);
        self::assertSame('reserved', $cadeau->status);
        self::assertSame('12.50', number_format((float) $cadeau->price, 2, '.', ''));
        self::assertSame('https://example.test/sokken', $cadeau->location_url);
        self::assertSame(6, $cadeau->reserved_by_user_id);

        self::assertNull(DB::table('cadeaus')->where('title', 'Vrij cadeau')->value('reserved_by_user_id'));
    }

    public function test_it_rejects_a_snapshot_without_required_tables(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('missing the cadeaus table');

        $this->importer()->import([
            ['type' => 'table', 'name' => 'foppers', 'data' => []],
        ]);
    }

    public function test_it_rolls_back_the_replacement_when_a_row_is_invalid(): void
    {
        DB::table('foppers')->insert([
            'inhoud' => 'Bestaande fop',
            'created_by_user_id' => 1,
            'created_for_user_id' => 2,
        ]);
        DB::table('cadeaus')->insert([
            'title' => 'Bestaand cadeau',
            'status' => 'available',
        ]);

        try {
            $this->importer()->import([
                ['type' => 'table', 'name' => 'foppers', 'data' => []],
                ['type' => 'table', 'name' => 'cadeaus', 'data' => [
                    [
                        'title' => 'Ongeldig cadeau',
                        'status' => 'ONBEKEND',
                        'createdBy' => 2,
                        'listId' => 4,
                    ],
                ]],
            ]);
            self::fail('Expected invalid status to abort the import.');
        } catch (InvalidArgumentException) {
            // Assert the transaction outcome below.
        }

        self::assertSame('Bestaande fop', DB::table('foppers')->value('inhoud'));
        self::assertSame('Bestaand cadeau', DB::table('cadeaus')->value('title'));
    }

    private function importer(): LegacySnapshotImporter
    {
        return new LegacySnapshotImporter(new CadeauIngestion(new EloquentCadeauPersistence));
    }
}
