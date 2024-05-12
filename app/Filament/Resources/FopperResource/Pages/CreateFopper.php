<?php

namespace App\Filament\Resources\FopperResource\Pages;

use App\Filament\Resources\FopperResource;
use Filament\Resources\Pages\CreateRecord;

class CreateFopper extends CreateRecord
{
    protected static string $resource = FopperResource::class;

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
