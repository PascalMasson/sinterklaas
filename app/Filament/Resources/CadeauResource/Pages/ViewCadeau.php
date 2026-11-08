<?php

namespace App\Filament\Resources\CadeauResource\Pages;

use App\Filament\Resources\CadeauResource;
use Filament\Resources\Pages\ViewRecord;

class ViewCadeau extends ViewRecord
{
    protected static string $resource = CadeauResource::class;

    protected static ?string $breadcrumb = '';

    protected function getHeaderActions(): array
    {
        return [];
    }
}
