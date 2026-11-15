<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FopperResource\Pages;
use App\Media\MediaPreview;
use App\Models\Fopper;
use App\Navigation\ParticipantListContext;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Navigation\NavigationItem;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class FopperResource extends Resource
{
    protected static ?string $model = Fopper::class;

    protected static ?string $slug = 'foppers/{targetId}';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-face-smile';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Textarea::make('inhoud')
                    ->rows(10)
                    ->required(),

                SpatieMediaLibraryFileUpload::make('images')
                    ->label('Afbeeldingen')
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
        $mediaPreview = new MediaPreview;

        return $table
            ->columns([

                TextColumn::make('createdByUser.name')
                    ->label('Aangemaakt door')
                    ->sortable(),

                TextColumn::make('inhoud')
                    ->wrap()
                    ->grow(),

                TextColumn::make('created_at')
                    ->label('Toegevoegd op')
                    ->date('d-m-Y')
                    ->sortable(),

                SpatieMediaLibraryImageColumn::make('images')
                    ->label('Afbeeldingen')
                    ->action(
                        Action::make('view-image')
                            ->label('Bekijk afbeeldingen')
                            ->visible(fn (Fopper $record): bool => $record->hasMedia())
                            ->modalContent(function (Fopper $record) use ($mediaPreview) {
                                return view('filament.pages.actions.view-images', [
                                    'images' => $mediaPreview->from($record->getMedia()),
                                ]);
                            })
                            ->modalSubmitAction(false)
                    ),
            ])
            ->filters([
            ])
            ->actions([
                EditAction::make()->label('')->visible(fn (Fopper $record) => $record->created_by_user_id == auth()->id()),
                DeleteAction::make()->label('')->visible(fn (Fopper $record) => $record->created_by_user_id == auth()->id()),
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
        return parent::getEloquentQuery()->where('created_for_user_id', static::getListId());
    }

    public static function getNavigationItems(): array
    {
        $context = static::context();

        return $context->navigationItems(auth()->id(), function (int $lijstId, string $name) use ($context): NavigationItem {
            return NavigationItem::make($name)
                ->group('Foppers')
                ->parentItem(static::getNavigationParentItem())
                ->icon(static::getNavigationIcon())
                ->activeIcon(static::getActiveNavigationIcon())
                ->isActiveWhen(fn () => $context->isCurrent(static::getRouteBaseName(), $lijstId))
                ->badge(static::getNavigationBadge(), color: static::getNavigationBadgeColor())
                ->badgeTooltip(static::getNavigationBadgeTooltip())
                ->sort(static::getNavigationSort())
                ->url(fn (): string => route('filament.default.resources.foppers.{targetId}.index', ['targetId' => $lijstId]));
        });
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

    public static function getTitleCasePluralModelLabel(): string
    {
        return 'Foppers voor '.static::getListName();
    }

    public static function canViewAny(): bool
    {
        return auth()->id() !== static::getListId();
    }

    private static function context(): ParticipantListContext
    {
        return new ParticipantListContext('targetId', 'targetId');
    }
}
