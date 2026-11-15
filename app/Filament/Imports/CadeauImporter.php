<?php

namespace App\Filament\Imports;

use App\Domain\Cadeaus\CadeauIngestion;
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
                ->fillRecordUsing(fn (): null => null),
            ImportColumn::make("price")
                ->fillRecordUsing(fn (): null => null),
            ImportColumn::make("location")
                ->fillRecordUsing(fn (): null => null),
            ImportColumn::make('visibility')
                ->fillRecordUsing(fn (): null => null),
            ImportColumn::make("created_by_user_id"),
            ImportColumn::make("list_user_id"),
            ImportColumn::make("reserved_by_user_id"),
        ];
    }

    public function beforeSave(): void
    {
        $this->record?->forceFill(app(CadeauIngestion::class)->canonicalize($this->data));
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
