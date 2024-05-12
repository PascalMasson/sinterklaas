<?php

namespace App\Filament\Resources\CadeauResource\Pages;

use App\Filament\Resources\CadeauResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditCadeau extends EditRecord
{
    protected static string $resource = CadeauResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return static::$resource::getUrl(parameters: ['listId' => static::$resource::getListId()]);
    }
}
