<?php

namespace App\Filament\Resources\CadeauResource\Pages;

use App\Filament\Resources\CadeauResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCadeau extends CreateRecord
{
    protected static string $resource = CadeauResource::class;

    protected function getHeaderActions(): array
    {
        return [

        ];
    }

    protected function getRedirectUrl(): string
    {
        return static::$resource::getUrl(parameters: ['listId' => static::$resource::getListId()]);
    }
}
