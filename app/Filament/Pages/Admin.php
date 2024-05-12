<?php

namespace App\Filament\Pages;

use App\Enums\CadeauStatus;
use App\Models\Cadeau;
use App\Models\User;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class Admin extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.pages.admin';
    protected static ?int $navigationSort = 100;

    public static function shouldRegisterNavigation(): bool
    {
        return in_array(auth()->id(), [1, 7]);
    }

    public function resetCadeaus(){
        Cadeau::where("status", "!=", CadeauStatus::AVAILABLE)
            ->where("deleted_at", null)
            ->update(["status" => CadeauStatus::AVAILABLE]);
        Notification::make()
            ->title("Cadeaus succesvol gereset naar vrij")
            ->success()
            ->send();
    }

    public function deletePurchased(){
        Cadeau::where("status", CadeauStatus::PURCHASED)
            ->where("deleted_at", null)
            ->delete();
        Notification::make()
            ->title("Cadeaus succesvol verwijderd")
            ->success()
            ->send();
    }

    public function draw(){
        $users = User::pluck("id")->toArray();
        shuffle($users);
        for($i = 0; $i < count($users); $i++){
            $user = User::find($users[$i]);
            $user->lot_id = $users[($i+1) % count($users)];
            $user->save();
        }

        Notification::make()
            ->title("Lootjes zijn getrokken!")
            ->success()
            ->send();
    }

    public function checkLootjes(){
        $lot_id_null_count = User::where("lot_id", null)->count();
        $duplicate_lot_id_count = User::groupBy("lot_id")
            ->havingRaw('COUNT(\'lot_id\') > 1')
            ->pluck("lot_id");
        if($lot_id_null_count === 0) {
            Notification::make()
                ->title("Iedereen heeft een lootje ingevuld")
                ->success()
                ->send();
        }else {
            Notification::make()
                ->title("Er zijn $lot_id_null_count lootjes die niet ingevuld zijn")
                ->warning()
                ->send();
        }

        if($duplicate_lot_id_count->count() > 0) {
            Notification::make()
                ->title(sprintf("Er zijn %s lootjes door meerdere personen ingevuld", $duplicate_lot_id_count->count()))
                ->warning()
                ->send();
        }else {
            Notification::make()
                ->title("Er zijn geen dubbele lootjes")
                ->success()
                ->send();
        }
    }
}
