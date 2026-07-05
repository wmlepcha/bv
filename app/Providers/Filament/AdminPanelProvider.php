<?php

namespace App\Providers\Filament;

use App\Http\Controllers\Filament\InquiryDocumentController;
use App\Support\Brand;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('bv-admin')
            ->path('bv-admin')
            ->login()
            ->brandName('Bilingual Vitae')
            ->brandLogo(fn (): string => request()->routeIs('filament.bv-admin.auth.login')
                ? Brand::loginLogoLight()
                : Brand::dashboardLogo())
            ->darkModeBrandLogo(fn (): string => request()->routeIs('filament.bv-admin.auth.login')
                ? Brand::loginLogoDark()
                : Brand::dashboardLogo())
            ->brandLogoHeight(fn (): string => request()->routeIs('filament.bv-admin.auth.login')
                ? Brand::loginLogoHeight()
                : Brand::dashboardLogoHeight())
            ->colors([
                'primary' => Color::generatePalette(Brand::primary()),
                'info' => Color::generatePalette(Brand::info()),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->navigationGroups([
                NavigationGroup::make()->label('Header'),
                NavigationGroup::make()->label('Footer'),
                NavigationGroup::make()->label('Homepage'),
                NavigationGroup::make()->label('About Page'),
                NavigationGroup::make()->label('Projects / Blog'),
            ])
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->routes(function (): void {
                Route::get(
                    'inquiries/{inquiry}/documents/{field}/download',
                    [InquiryDocumentController::class, 'download'],
                )->name('inquiries.documents.download');
            });
    }
}
