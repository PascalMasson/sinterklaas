<?php

namespace App\Navigation;

use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class NavigationManager extends \Filament\Navigation\NavigationManager
{
    public function get(): array
    {
        return collect(parent::get())->sort(function (NavigationGroup $a, NavigationGroup $b) {
            if($a->getLabel() === null){
                return 1;
            }
            return $b->getLabel() <=> $a->getLabel();
        })->all();
    }
}
