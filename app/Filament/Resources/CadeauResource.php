<?php

namespace App\Filament\Resources;

use App\Domain\Cadeaus\CadeauReservationPolicy;
use App\Domain\Cadeaus\CadeauVisibilityPolicy;
use App\Enums\CadeauStatus;
use App\Enums\CadeauVisibility;
use BackedEnum;
use App\Filament\Clusters\Lijstjes;
use App\Filament\Resources\CadeauResource\Pages;
use App\Models\Cadeau;
use App\Media\MediaPreview;
use App\Navigation\ParticipantListContext;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Navigation\NavigationItem;
use Filament\Resources\Resource;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
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

class CadeauResource extends Resource
{
    protected static ?string $model = Cadeau::class;

    protected static ?string $slug = 'cadeaus/{listId}';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-gift';
    protected static bool $shouldRegisterNavigation = true;

    public static function form(Schema $schema): Schema
    {
        $recipientName = static::getListName();
        $visibilityPolicy = new CadeauVisibilityPolicy();

        return $schema
            ->schema([
                TextInput::make('title')
                    ->label("Titel")
                    ->required(),

                Textarea::make('description')
                    ->label("Omschrijving"),

                TextInput::make('status')
                    ->label("Status")
                    ->required()
                    ->visibleOn("view")
                    ->hidden(fn () => static::isOwnList()),

                TextInput::make('price')
                    ->label("Prijs")
                    ->prefix("€")
                    ->numeric(),

                Select::make('visibility')
                    ->label('Zichtbaarheid')
                    ->options(CadeauVisibility::options($recipientName))
                    ->default(CadeauVisibility::HIDDEN->value)
                    ->hidden(fn () => ! $visibilityPolicy->canConfigure(auth()->user(), static::getListId()))
                    ->dehydrated(fn () => $visibilityPolicy->canConfigure(auth()->user(), static::getListId()))
                    ->required(),

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
        $reservationPolicy = new CadeauReservationPolicy();
        $mediaPreview = new MediaPreview();

        return $table
            ->columns(array(
                TextColumn::make('visibility')
                    ->label('Zichtbaarheid')
                    ->badge()
                    ->hidden(fn () => ! (new CadeauVisibilityPolicy())->canConfigure(auth()->user(), static::getListId()))
                    ->color(fn($state) => match (($state instanceof CadeauVisibility) ? $state : CadeauVisibility::from($state)) {
                        CadeauVisibility::PUBLIC => 'success',
                        CadeauVisibility::HIDDEN => 'warning',
                        CadeauVisibility::PRIVATE => 'gray',
                    })
                    ->formatStateUsing(fn($state) => (($state instanceof CadeauVisibility) ? $state : CadeauVisibility::from($state))->toHumanReadable())
                    ->sortable(),
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
                            ->visible(fn (Cadeau $record): bool => $record->hasMedia())
                            ->modalContent(function (Cadeau $record) use ($mediaPreview) {
                                return view('filament.pages.actions.view-images', [
                                    'images' => $mediaPreview->from($record->getMedia()),
                                ]);
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
                    ->disabled(fn (Cadeau $record): bool => ! $reservationPolicy->canChangeStatus($record, auth()->user()))
                    ->beforeStateUpdated(function (Cadeau $record, string $state) use ($reservationPolicy): void {
                        $reservationPolicy->changeStatus(
                            $record,
                            CadeauStatus::from($state),
                            auth()->user(),
                        );
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
                ViewAction::make()
                    ->label('')
                    ->visible(fn (Cadeau $record) => (new CadeauVisibilityPolicy())->canView($record, auth()->user())),
                EditAction::make()
                    ->label("")
                    ->visible(fn (Cadeau $record) => (new CadeauVisibilityPolicy())->canEdit($record, auth()->user())),
                DeleteAction::make()->label("")->hidden(fn() => static::getListId() !== auth()->id()),
                RestoreAction::make(),
                ForceDeleteAction::make(),
            ])
            ->paginated([25, 50, 100])
            ->recordClasses(function (Cadeau $record): array {
                if (static::getListId() === auth()->id()) {
                    return [];
                }

                return match ($record->status) {
                    CadeauStatus::AVAILABLE => ['cadeau-status-available'],
                    CadeauStatus::RESERVED => ['cadeau-status-reserved'],
                    CadeauStatus::PURCHASED => ['cadeau-status-purchased'],
                };
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
            'view' => Pages\ViewCadeau::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {;
        return parent::getEloquentQuery()
            ->where("list_user_id", static::getListId())
            ->visibleFor(auth()->user());
    }

    public static function getUrl(?string $name = null, array $parameters = [], bool $isAbsolute = true, ?string $panel = null, ?Model $tenant = null, bool $shouldGuessMissingParameters = false, ?string $configuration = null): string
    {
        $parameters = static::context()->withCurrentId($parameters);

        return parent::getUrl($name, $parameters, $isAbsolute, $panel, $tenant, $shouldGuessMissingParameters, $configuration);
    }

    public static function getListId(): ?int
    {
        return static::context()->id();
    }

    protected static function getListName(): ?string
    {
        return static::context()->name();
    }

    public static function getNavigationItems(): array
    {
        $context = static::context();

        return $context->navigationItems(null, function (int $lijstId, string $name) use ($context): NavigationItem {
            return NavigationItem::make($name)
                ->group("Lijstjes")
                ->parentItem(static::getNavigationParentItem())
                ->icon(static::getNavigationIcon())
                ->activeIcon(static::getActiveNavigationIcon())
                ->isActiveWhen(fn () => $context->isCurrent(static::getRouteBaseName(), $lijstId))
                ->badge(static::getNavigationBadge(), color: static::getNavigationBadgeColor())
                ->badgeTooltip(static::getNavigationBadgeTooltip())
                ->sort(static::getNavigationSort())
                ->url(fn(): string => route("filament.default.resources.cadeaus.{listId}.index", ['listId' => $lijstId]));
        });
    }

    public static function getTitleCasePluralModelLabel(): string
    {
        return "Lijstje van " . static::getListName();
    }

    protected static function isOwnList(): bool
    {
        if (! auth()->check()) {
            return false;
        }

        $listId = static::getListId();

        return $listId !== null && (int) $listId === (int) auth()->id();
    }

    private static function context(): ParticipantListContext
    {
        return ParticipantListContext::forList();
    }

}
