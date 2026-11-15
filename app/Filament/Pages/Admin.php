<?php

namespace App\Filament\Pages;

use App\Domain\Cadeaus\CadeauImportNormalizer;
use App\Domain\Cadeaus\CadeauReservationPolicy;
use App\Enums\CadeauStatus;
use App\Filament\Imports\CadeauImporter;
use App\Models\Cadeau;
use App\Models\Fopper;
use App\Models\User;
use BackedEnum;
use Filament\Actions\ImportAction;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

class Admin extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected string $view = 'filament.pages.admin';
    protected static ?int $navigationSort = 100;

    public $databaseJson;

    public static function shouldRegisterNavigation(): bool
    {
        return in_array(auth()->id(), [1, 7]);
    }

    public function resetCadeaus(){
        $reservationPolicy = new CadeauReservationPolicy();

        DB::transaction(function () use ($reservationPolicy): void {
            Cadeau::where("status", "!=", CadeauStatus::AVAILABLE)
                ->whereNull("deleted_at")
                ->get()
                ->each(function (Cadeau $cadeau) use ($reservationPolicy): void {
                    $reservationPolicy->reset($cadeau);
                    $cadeau->save();
                });
        });

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
        $users = User::whereNot("id", auth()->id())->pluck("id")->toArray();
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
        $duplicate_lot_id_count = User::whereNot("lot_id", null)
            ->groupBy("lot_id")
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

        $normalizer = new CadeauImportNormalizer();

        DB::transaction(function () use ($tables, $normalizer): void {
            DB::table('foppers')->delete();
            foreach ($tables['foppers'] as $old) {
                $new = new Fopper();
                $new->inhoud = $old->description;
                $new->created_by_user_id = $old->fopperVan;
                $new->created_for_user_id = $old->fopperVoor;
                $new->save();
            }

            DB::table('cadeaus')->delete();
            DB::table('media')->delete();

            foreach ($tables['cadeaus'] as $old) {
                $new = new Cadeau();
                $new->forceFill($normalizer->normalize([
                    'title' => $old->title,
                    'status' => $old->status,
                    'price' => $old->prijs ?? null,
                    'location' => $old->location,
                    'created_by_user_id' => $old->createdBy,
                    'list_user_id' => $old->listId,
                    'reserved_by_user_id' => $old->status !== 'VRIJ'
                        ? $old->reservedBy
                        : null,
                ]));
                $new->save();
            }
        });

        Notification::make()
            ->title("Database is succesvol geimporteerd")
            ->success()
            ->send();

        $this->databaseJson = null;
    }
}
