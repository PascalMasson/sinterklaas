<?php

namespace App\Filament\Pages;

use App\Models\User;
use Filament\Pages\Page;
use Illuminate\Support\Collection;

class Lot extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-ticket';

    protected static string $view = 'filament.pages.lot';

    protected static ?string $title = "Mijn lootje";

    public array|Collection $users;

    public ?int $selectedLotID;
    public ?int $mijnLotID;

    public function __construct()
    {
        $this->users = User::where("id", "!=" ,auth()->id())->get();
        $this->mijnLotID = auth()->user()->lot_id;
        $this->selectedLotID = $this->mijnLotID;

    }

    public function updatedSelectedLotID(int $id)
    {
        $user = auth()->user();
        $user->lot_id = $id;
        $user->save();
        $this->mijnLotID = $id;
    }

}
