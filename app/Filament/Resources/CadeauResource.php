<?php

namespace App\Filament\Resources;

use App\Enums\CadeauStatus;
use App\Filament\Clusters\Lijstjes;
use App\Filament\Resources\CadeauResource\Pages;
use App\Models\Cadeau;
use App\Models\User;
use Cheesegrits\FilamentGoogleMaps\Fields\Geocomplete;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Navigation\NavigationItem;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ForceDeleteAction;
use Filament\Tables\Actions\ForceDeleteBulkAction;
use Filament\Tables\Actions\RestoreAction;
use Filament\Tables\Actions\RestoreBulkAction;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Cache;

class CadeauResource extends Resource
{
    protected static ?string $model = Cadeau::class;

    protected static ?string $slug = 'cadeaus/{listId}';

    protected static ?string $navigationIcon = 'heroicon-o-gift';
    protected static bool $shouldRegisterNavigation = true;

    protected static $cachedName = null;


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('title')
                    ->label("Titel")
                    ->required(),

                Textarea::make('description')
                    ->label("Omschrijving"),

                TextInput::make('status')
                    ->label("Status")
                    ->required()
                    ->visibleOn("view"),

                TextInput::make('price')
                    ->label("Prijs")
                    ->prefix("€")
                    ->required()
                    ->numeric(),

                Radio::make('location_type')
                    ->label("Locatie")
                    ->options([
                        'website' => 'Website',
                        'overig' => 'Anders',
                    ])
                    ->inline()
                    ->default("website")
                    ->live(),

                TextInput::make('location_url')
                    ->label("Link")
                    ->url()
                    ->hidden(fn(Get $get) => $get('location_type') !== 'website')
                    ->url(),

                TextInput::make('location_other')
                    ->label("Locatie")
                    ->hidden(fn(Get $get) => $get('location_type') !== 'overig'),

                SpatieMediaLibraryFileUpload::make("images")
                    ->label("Afbeeldingen")
                    ->reorderable()
                    ->responsiveImages()
                    ->multiple(),

                Hidden::make("list_user_id")
                    ->default(static::getListId()),

            ])->inlineLabel()->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns(array(
                TextColumn::make('title')
                    ->label("Titel")
                    ->searchable()
                    ->sortable(),
                TextColumn::make('description')
                    ->limit(50)
                    ->wrap()
                    ->label("Omschrijving"),
                TextColumn::make('price')
                    ->sortable()
                    ->money("EUR")
                    ->label("Prijs"),
                TextColumn::make('location_type')
                    ->label("Locatie")
                    ->formatStateUsing(function (string $state, Cadeau $record) {
                        switch ($state) {
                            case "website":
                                $url = parse_url($record->location_url);
                                if(isset($url['host'])){
                                    return $url['host'];
                                }
                                else return $record->location_url;
                            case "overig":
                                return $record->location_other;
                            default:
                                return "Onbekend";
                        }
                    })
                    ->url(fn(Cadeau $record) => $record->location_url)
                    ->openUrlInNewTab(),
                SpatieMediaLibraryImageColumn::make("images")
                    ->label("Afbeeldingen")
                    ->action(
                        Action::make("view-image")
                            ->label("Bekijk afbeeldingen")
                            ->modalContent(function (Cadeau $record) {
                                $images = [];
                                foreach ($record->getMedia() as $media) {
                                    $images[] = [
                                        'imgSrc' => $media->getUrl(),
                                        'imgAlt' => $media->name,
                                    ];
                                }
                                return view('filament.pages.actions.view-images', ['images' => $images]);
                            })
                            ->modalSubmitAction(false)
                    ),
                SelectColumn::make("status")
                    ->label("Status")
                    ->options([
                        CadeauStatus::AVAILABLE->value => 'Vrij',
                        CadeauStatus::RESERVED->value => 'Gereserveerd',
                        CadeauStatus::PURCHASED->value => 'Gekocht',
                    ])
                    ->sortable()
                    ->searchable()
                    ->alignEnd()
                    ->hidden(fn() => static::getListId() == auth()->id())
                    ->disabled(function (Cadeau $record){
                        $edit_ids = [auth()->id(), auth()->user()->partnerId];
                        if($record->status == CadeauStatus::AVAILABLE){
                            return false;
                        }
                        if(in_array($record->reserved_by_user_id, $edit_ids)){
                            return false;
                        }

                        return true;
                    })
                    ->beforeStateUpdated(function (Cadeau $record, $state) {
                        if($state == CadeauStatus::RESERVED->value || $state == CadeauStatus::PURCHASED->value){
                            $record->reserved_by_user_id = auth()->id();
                        }
                        if($state == CadeauStatus::AVAILABLE->value){
                            $record->reserved_by_user_id = null;
                        }
                    }),

            ))
            ->filters([
                SelectFilter::make("status")
                    ->label("Status")
                    ->options([
                        CadeauStatus::AVAILABLE->value => 'Vrij',
                        CadeauStatus::RESERVED->value => 'Gereserveerd',
                        CadeauStatus::PURCHASED->value => 'Gekocht',
                    ])
                    ->attribute("status")
                    ->hidden(fn() => static::getListId() == auth()->id())
            ])
            ->filtersLayout(FiltersLayout::AboveContent)
            ->actions([
                EditAction::make()->label("")->hidden(fn() => static::getListId() !== auth()->id()),
                DeleteAction::make()->label("")->hidden(fn() => static::getListId() !== auth()->id()),
                RestoreAction::make(),
                ForceDeleteAction::make(),
            ])
            ->paginated([25, 50, 100])
            ->recordClasses(function (Cadeau $record) {
                if(static::getListId() != auth()->id()) {
                    return match ($record->status) {
                        CadeauStatus::AVAILABLE => 'bg-green-300 dark:bg-green-800 hover:bg-green-300 hover:dark:bg-green-800',
                        CadeauStatus::RESERVED => 'bg-yellow-300 dark:bg-yellow-800 hover:bg-yellow-300 hover:dark:bg-yellow-800',
                        CadeauStatus::PURCHASED => 'bg-red-300 dark:bg-red-800 hover:bg-red-300 hover:dark:bg-red-800',
                    };
                }
                return "";
            });
//            ->defaultGroup(
//                Group::make("status")
//                    ->getTitleFromRecordUsing(fn(Cadeau $record) => $record->status->toHumanReadable())
//                    ->titlePrefixedWithLabel(true)
//            );
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCadeaus::route('/'),
            'create' => Pages\CreateCadeau::route('/create'),
            'edit' => Pages\EditCadeau::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {;
        return parent::getEloquentQuery()
            ->where("list_user_id", static::getListId());
    }

    public static function getUrl(string $name = 'index', array $parameters = [], bool $isAbsolute = true, ?string $panel = null, ?Model $tenant = null): string
    {
        if(!array_key_exists("listId", $parameters)){
            $parameters["listId"] = static::getListId();
        }
        return parent::getUrl($name, $parameters, $isAbsolute, $panel, $tenant); // TODO: Change the autogenerated stub
    }

    public static function getListId(): ?int
    {
        return request()->route()->parameter('listId') ?? session()->get('listId') ?? null;
    }


    protected static function getListName(): ?string
    {
        if (is_null(static::$cachedName)) {
            static::$cachedName = User::find(static::getListId())->name;
        }
        return static::$cachedName;
    }


    public static function getNavigationItems(): array
    {
        $lijstIds = Cache::remember("resource-list-id-nav-items", 3600, fn ()=> User::orderBy("name")->select('id', 'name')->pluck("name", "id")->toArray());
        $items = [];

        foreach ($lijstIds as $lijstId => $name) {
            $items[] = NavigationItem::make($name)
                ->group("Lijstjes")
                ->parentItem(static::getNavigationParentItem())
                ->icon(static::getNavigationIcon())
                ->activeIcon(static::getActiveNavigationIcon())
                ->isActiveWhen(fn () => (request()->routeIs(static::getRouteBaseName() . '.*') && request()->route()->parameter('listId') == $lijstId))
                ->badge(static::getNavigationBadge(), color: static::getNavigationBadgeColor())
                ->badgeTooltip(static::getNavigationBadgeTooltip())
                ->sort(static::getNavigationSort())
                ->url(fn(): string => route("filament.default.resources.cadeaus.{listId}.index", ['listId' => $lijstId]));
        }

        return $items;
    }

    public static function getTitleCasePluralModelLabel(): string
    {
        return "Lijstje van " . static::getListName();
    }

}
