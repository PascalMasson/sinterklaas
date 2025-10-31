<?php

namespace App\Filament\Imports;

use App\Enums\CadeauStatus;
use App\Enums\CadeauVisibility;
use App\Models\Cadeau;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;

class CadeauImporter extends Importer
{
    protected static ?string $model = Cadeau::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make("title"),
            ImportColumn::make("description"),
            ImportColumn::make("status")
                ->fillRecordUsing(function(Cadeau $record, string $state) {
                    $record->status = match ($state) {
                        'VRIJ' => CadeauStatus::AVAILABLE,
                        'GERESERVEERD' => CadeauStatus::RESERVED,
                        'GEKOCHT' => CadeauStatus::PURCHASED,
                        default => throw new \Exception("Unknown status: $state")
                    };
                }),
            ImportColumn::make("price")
                ->castStateUsing(fn ($state) => str($state ?? "0")->replace(',', '.')->toString()),
            ImportColumn::make("location")
                ->fillRecordUsing(function (Cadeau $record, string $state) {
                    if (filter_var($state, FILTER_VALIDATE_URL)) {
                        $record->location_url = $state;
                        $record->location_type = "website";
                    } else {
                        $record->location_other = $state;
                        $record->location_type = "overig";
                    }
                }),
            ImportColumn::make('visibility')
                ->fillRecordUsing(function (Cadeau $record, string $state) {
                    $record->visibility = match (strtoupper($state)) {
                        'PUBLIC' => CadeauVisibility::PUBLIC,
                        'HIDDEN' => CadeauVisibility::HIDDEN,
                        'PRIVATE' => CadeauVisibility::PRIVATE,
                        default => CadeauVisibility::PUBLIC,
                    };
                }),
            ImportColumn::make("created_by_user_id"),
            ImportColumn::make("list_user_id"),
            ImportColumn::make("reserved_by_user_id"),
        ];
    }

    public function beforeSave(){
        \Debugbar::info(json_encode($this->data));
    }

    public function resolveRecord(): ?Cadeau
    {
        // return Cadeau::firstOrNew([
        //     // Update existing records, matching them by `$this->data['column_name']`
        //     'email' => $this->data['email'],
        // ]);

        return new Cadeau();
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your cadeau import has completed and ' . number_format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to import.';
        }

        return $body;
    }
}
