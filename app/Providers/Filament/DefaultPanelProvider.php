<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Admin;
use App\Filament\Pages\Auth\Login;
use App\Filament\Pages\Lot;
use App\Filament\Pages\MijnReserveringen;
use App\Filament\Pages\Regels;
use App\Filament\Resources\CadeauResource;
use App\Filament\Resources\FopperResource;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationBuilder;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Assets\Css;
use Filament\Support\Assets\Js;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Width;
use Filament\Support\Facades\FilamentAsset;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Number;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class DefaultPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('default')
            ->path('')
            ->login(Login::class)
            ->colors([
                'primary' => Color::Amber,
            ])
            ->navigation(
                function (NavigationBuilder $builder) {
                    if(auth()->id() == 1 || auth()->id() == 7){
                        $admin = Admin::getNavigationItems();
                    }else {
                        $admin = [];
                    }
                    return $builder
                        ->groups([
                            NavigationGroup::make("Lijstjes")
                                ->items(CadeauResource::getNavigationItems()),
                            NavigationGroup::make("Foppers")
                                ->items(FopperResource::getNavigationItems()),
                        ])
                        ->items([
                            ...MijnReserveringen::getNavigationItems(),
                            ...Lot::getNavigationItems(),
                            ...Regels::getNavigationItems(),
                            ...$admin
                        ]);
                }
            )
            ->breadcrumbs(false)
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->discoverClusters(in: app_path('Filament/Clusters'), for: 'App\\Filament\\Clusters')
            ->pages([
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
                Widgets\FilamentInfoWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->topNavigation()
            ->maxContentWidth(Width::Full)
            ->assets([
                Css::make('cadeau-status', asset('css/cadeau-status.css')),
            ]);
    }

    public function boot()
    {
        Number::useLocale('nl');

        FilamentAsset::register([
            Js::make("embla", "https://unpkg.com/embla-carousel/embla-carousel.umd.js")
        ]);

    }
}
