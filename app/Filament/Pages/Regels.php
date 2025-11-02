<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Pages\Page;

class Regels extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-list-bullet';

    protected string $view = 'filament.pages.regels';
}
