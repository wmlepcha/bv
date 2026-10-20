<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Auth\Login;
use App\Filament\Pages\Dashboard;
use App\Http\Controllers\Filament\InquiryDocumentController;
use App\Http\Controllers\Filament\PaymentSlipController;
use App\Support\Brand;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\HtmlString;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('bv-admin')
            ->path('bv-admin')
            ->login(Login::class)
            ->brandName(Brand::trademarkedName())
            ->brandLogo(fn () => view('filament.brand-logo', [
                'src' => request()->routeIs('filament.bv-admin.auth.login')
                    ? Brand::loginLogoLight()
                    : Brand::dashboardLogoLight(),
            ]))
            ->darkModeBrandLogo(fn () => view('filament.brand-logo', [
                'src' => request()->routeIs('filament.bv-admin.auth.login')
                    ? Brand::loginLogoDark()
                    : Brand::dashboardLogoDark(),
            ]))
            ->brandLogoHeight(fn (): string => request()->routeIs('filament.bv-admin.auth.login')
                ? Brand::loginLogoHeight()
                : Brand::dashboardLogoHeight())
            ->colors([
                'primary' => Color::generatePalette(Brand::primary()),
                'info' => Color::generatePalette(Brand::info()),
            ])
            ->renderHook(PanelsRenderHook::HEAD_END, fn (): HtmlString => new HtmlString(<<<'HTML'
                <style>
                    .fi-body { background: #eef0f5; }
                    .dark .fi-body { background: var(--gray-950); }
                    .fi-page-dashboard .fi-grid, .fi-page-dashboard .fi-wi { gap: .875rem !important; }
                    .fi-section, .bv-card {
                        border-radius: 1rem;
                        background: #fff;
                        box-shadow: none;
                        --tw-ring-color: transparent;
                    }
                    .dark .bv-card { background: var(--gray-900); }
                    .fi-wi-chart .fi-section-header-heading, .bv-card-title { font-size: .95rem; font-weight: 600; }
                    .bv-card { padding: .9rem 1rem; }
                    .bv-kpis { display: grid; gap: .875rem; grid-template-columns: repeat(auto-fit, minmax(11rem, 1fr)); }
                    .bv-kpi-head { display: flex; align-items: center; justify-content: space-between; }
                    .bv-kpi-label { font-size: .8rem; font-weight: 500; color: var(--gray-500); }
                    .bv-kpi-value { font-size: 1.65rem; font-weight: 700; letter-spacing: -.02em; line-height: 1.2; margin-top: .25rem; }
                    .bv-dec { color: var(--gray-400); font-weight: 600; }
                    .bv-kpi-foot { margin-top: .5rem; padding-top: .5rem; border-top: 1px solid var(--gray-100); font-size: .72rem; color: var(--gray-500); }
                    .bv-icon { display: inline-flex; width: 1.9rem; height: 1.9rem; align-items: center; justify-content: center; border-radius: .6rem; }
                    .bv-tint-violet { background: #ede9fe; color: #7c3aed; }
                    .bv-tint-blue { background: #dbeafe; color: #2563eb; }
                    .bv-tint-green { background: #dcfce7; color: #16a34a; }
                    .bv-tint-amber { background: #fef3c7; color: #d97706; }
                    .bv-tint-orange { background: #ffedd5; color: #ea580c; }
                    .bv-seg { display: flex; gap: 3px; height: .7rem; margin: .75rem 0; }
                    .bv-seg span { border-radius: .35rem; }
                    .bv-legend { list-style: none; margin: 0; padding: 0; max-height: 9.5rem; overflow-y: auto; }
                    .bv-legend li { display: flex; align-items: center; gap: .6rem; padding: .35rem 0; font-size: .82rem; }
                    .bv-legend-name { flex: 1; display: flex; flex-direction: column; line-height: 1.15; }
                    .bv-legend-name small { color: var(--gray-400); font-size: .7rem; }
                    .bv-dot { width: .6rem; height: .6rem; border-radius: .2rem; flex: none; }
                </style>
                HTML))
            ->sidebarWidth('16rem')
            ->sidebarCollapsibleOnDesktop()
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->discoverClusters(in: app_path('Filament/Clusters'), for: 'App\Filament\Clusters')
            ->navigationGroups([
                NavigationGroup::make()->label('Services Page'),
                NavigationGroup::make()->label('Header'),
                NavigationGroup::make()->label('Footer'),
                NavigationGroup::make()->label('Homepage'),
                NavigationGroup::make()->label('About Page'),
                NavigationGroup::make()->label('Accounts'),
                NavigationGroup::make()->label('Inquiries'),
                NavigationGroup::make()->label('Projects / Blog'),
            ])
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
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

                Route::get(
                    'payments/{payment}/slip',
                    [PaymentSlipController::class, 'download'],
                )->name('payments.slip.download');
            });
    }
}
