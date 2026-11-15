<?php

namespace App\Domain\LegacySnapshots;

use App\Domain\Cadeaus\CadeauIngestion;
use App\Models\Fopper;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class LegacySnapshotImporter
{
    public function __construct(
        private CadeauIngestion $ingestion,
    ) {}

    /**
     * @param  array<int, mixed>  $snapshot
     */
    public function import(array $snapshot): void
    {
        $tables = $this->tablesFrom($snapshot);

        DB::transaction(function () use ($tables): void {
            DB::table('foppers')->delete();

            foreach ($tables['foppers'] as $row) {
                $fopper = new Fopper;
                $fopper->forceFill([
                    'inhoud' => $this->required($row, 'description'),
                    'created_by_user_id' => $this->required($row, 'fopperVan'),
                    'created_for_user_id' => $this->required($row, 'fopperVoor'),
                ]);
                $fopper->save();
            }

            $this->ingestion->replace(array_map(
                fn (array $row): array => [
                    'title' => $this->required($row, 'title'),
                    'status' => $this->required($row, 'status'),
                    'price' => $row['prijs'] ?? null,
                    'location' => $row['location'] ?? null,
                    'created_by_user_id' => $this->required($row, 'createdBy'),
                    'list_user_id' => $this->required($row, 'listId'),
                    'reserved_by_user_id' => $row['reservedBy'] ?? null,
                ],
                $tables['cadeaus'],
            ));
        });
    }

    /**
     * @param  array<int, mixed>  $snapshot
     * @return array{foppers: list<array<string, mixed>>, cadeaus: list<array<string, mixed>>}
     */
    private function tablesFrom(array $snapshot): array
    {
        $tables = [];

        foreach ($snapshot as $table) {
            if (! is_array($table) || ($table['type'] ?? null) !== 'table') {
                continue;
            }

            $name = $table['name'] ?? null;
            if (! in_array($name, ['foppers', 'cadeaus'], true)) {
                continue;
            }

            if (! is_array($table['data'] ?? null)) {
                throw new InvalidArgumentException("Legacy snapshot table {$name} has no row data.");
            }

            foreach ($table['data'] as $row) {
                if (! is_array($row)) {
                    throw new InvalidArgumentException("Legacy snapshot table {$name} contains an invalid row.");
                }
            }

            $tables[$name] = array_values($table['data']);
        }

        foreach (['foppers', 'cadeaus'] as $name) {
            if (! array_key_exists($name, $tables)) {
                throw new InvalidArgumentException("Legacy snapshot is missing the {$name} table.");
            }
        }

        return $tables;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function required(array $row, string $key): mixed
    {
        if (! array_key_exists($key, $row)) {
            throw new InvalidArgumentException("Legacy snapshot row is missing {$key}.");
        }

        return $row[$key];
    }
}
