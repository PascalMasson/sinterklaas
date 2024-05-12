<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FopperResource\Pages;
use App\Models\Cadeau;
use App\Models\Fopper;
use App\Models\User;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
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
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Cache;

class FopperResource extends Resource
{
    protected static ?string $model = Fopper::class;

    protected static ?string $slug = 'foppers/{targetId}';

    protected static ?string $navigationIcon = 'heroicon-o-face-smile';

    protected static $cachedName = null;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Textarea::make('inhoud')
                    ->rows(10)
                    ->required(),

                SpatieMediaLibraryFileUpload::make("images")
                    ->label("Afbeeldingen")
                    ->reorderable()
                    ->responsiveImages()
                    ->multiple(),

                Hidden::make('created_by_user_id')
                    ->default(auth()->id()),

                Hidden::make('created_for_user_id')
                    ->default(static::getListId()),
            ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([

                TextColumn::make('createdByUser.name')
                    ->label("Aangemaakt door")
                    ->sortable(),

                TextColumn::make('inhoud')
                    ->wrap()
                    ->grow(),

                SpatieMediaLibraryImageColumn::make("images")
                    ->label("Afbeeldingen")
                    ->action(
                        Action::make("view-image")
                            ->label("Bekijk afbeeldingen")
                            ->modalContent(function (Fopper $record) {
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
            ])
            ->filters([
            ])
            ->actions([
                EditAction::make()->label("")->visible(fn(Fopper $record) => $record->created_by_user_id == auth()->id()),
                DeleteAction::make()->label("")->visible(fn(Fopper $record) => $record->created_by_user_id == auth()->id()),
                RestoreAction::make(),
                ForceDeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFoppers::route('/'),
            'create' => Pages\CreateFopper::route('/create'),
            'edit' => Pages\EditFopper::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where("created_for_user_id", static::getListId());
    }

    public static function getNavigationItems(): array
    {
        $lijstIds = Cache::remember("resource-list-id-nav-items", 3600, fn ()=> User::orderBy("name")->select('id', 'name')->pluck("name", "id")->toArray());

        $currentUser = auth()->id();

        $items = [];
        foreach ($lijstIds as $lijstId => $name) {
            if($lijstId == $currentUser){
                continue;
            }
            $items[] = NavigationItem::make($name)
                ->group("Foppers")
                ->parentItem(static::getNavigationParentItem())
                ->icon(static::getNavigationIcon())
                ->activeIcon(static::getActiveNavigationIcon())
                ->isActiveWhen(fn () => (request()->routeIs(static::getRouteBaseName() . '.*') && request()->route()->parameter('listId') == $lijstId))
                ->badge(static::getNavigationBadge(), color: static::getNavigationBadgeColor())
                ->badgeTooltip(static::getNavigationBadgeTooltip())
                ->sort(static::getNavigationSort())
                ->url(fn(): string => route("filament.default.resources.foppers.{targetId}.index", ['targetId' => $lijstId]));
        }
        return $items;
    }

    public static function getUrl(string $name = 'index', array $parameters = [], bool $isAbsolute = true, ?string $panel = null, ?Model $tenant = null): string
    {
        if(!array_key_exists("targetId", $parameters)){
            $parameters["targetId"] = static::getListId();
        }
        return parent::getUrl($name, $parameters, $isAbsolute, $panel, $tenant); // TODO: Change the autogenerated stub
    }

    public static function getListId(): ?int
    {
        return request()->route()->parameter('targetId') ?? session()->get('targetId') ?? null;
    }

    protected static function getListName(): ?string
    {
        if (is_null(static::$cachedName)) {
            static::$cachedName = User::find(static::getListId())->name;
        }
        return static::$cachedName;
    }

    public static function getTitleCasePluralModelLabel(): string
    {
        return "Foppers voor " . static::getListName();
    }

    public static function canViewAny(): bool
    {
        return auth()->id() !== static::getListId();
    }
}
