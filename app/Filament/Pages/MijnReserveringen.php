<?php

namespace App\Filament\Pages;

use App\Filament\Resources\CadeauResource;
use App\Models\Cadeau;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;

class MijnReserveringen extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-gift-top';

    protected static string $view = 'filament.pages.mijn-reserveringen';

    public function table(Table $table): Table
    {
        session()->forget("listId");
        return CadeauResource::table($table)->query(function(){
            return Cadeau::whereIn("reserved_by_user_id",[auth()->user()->id, auth()->user()->partnerId]);
        })
            ->defaultGroup(Group::make("list_user_id")->getTitleFromRecordUsing(fn(Cadeau $cadeau) => $cadeau->listUser->name)->label("Lijstje van"));
    }

}
