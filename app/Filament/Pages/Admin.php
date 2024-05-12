<?php

namespace App\Filament\Pages;

use App\Enums\CadeauStatus;
use App\Filament\Imports\CadeauImporter;
use App\Models\Cadeau;
use App\Models\Fopper;
use App\Models\User;
use Exception;
use Filament\Actions\ImportAction;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

class Admin extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.pages.admin';
    protected static ?int $navigationSort = 100;

    public $databaseJson;

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

    public function saveDatabaseJson()
    {
        $data = $this->validate([
            'databaseJson' => 'required|file|mimes:json',
        ]);

        $filecontents = file_get_contents($data['databaseJson']->getRealPath());
        $all_data = json_decode($filecontents, null, 512, JSON_THROW_ON_ERROR);
        //filter all data where type is table
        $tablesdata = array_filter($all_data, fn ($item): bool => $item->type == 'table' && $item->name != 'gebruikers');
        $tables = [];
        foreach ($tablesdata as $tabledata) {
            $tables[$tabledata->name] = $tabledata->data;
        }

        DB::table('foppers')->truncate();
        foreach ($tables['foppers'] as $old) {
            $new = new Fopper();
            $new->inhoud = $old->description;
            $new->created_by_user_id = $old->fopperVan;
            $new->created_for_user_id = $old->fopperVoor;
            $new->save();
        }

        DB::table('cadeaus')->truncate();

        foreach ($tables['cadeaus'] as $old) {
            $new = new Cadeau();
            $new->title = $old->title;

            $new->status = match ($old->status) {
                'VRIJ' => CadeauStatus::AVAILABLE,
                'GERESERVEERD' => CadeauStatus::RESERVED,
                'GEKOCHT' => CadeauStatus::PURCHASED,
                default => throw new Exception("Unknown status: " . $old->Status)
            };

            $new->price = $old->prijs ?? 0;

            if(filter_var($old->location, FILTER_VALIDATE_URL)) {
                $new->location_url = $old->location;
                $new->location_type = 'website';
            } else{
                $new->location_other = $old->location;
                $new->location_type = 'overig';
            }

            $new->created_by_user_id = $old->createdBy;
            $new->list_user_id = $old->listId;
            if ($old->status != 'VRIJ') {
                $new->reserved_by_user_id = $old->reservedBy;
            }
            $new->save();
        }

        Notification::make()
            ->title("Database is succesvol geimporteerd")
            ->success()
            ->send();

        $this->databaseJson = null;
    }
}
