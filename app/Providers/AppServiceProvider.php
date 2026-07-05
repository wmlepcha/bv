<?php

namespace App\Providers;

use App\Inertia\DomainPageFinder;
use App\Models\AboutCoreValue;
use App\Models\AboutJourneyMilestone;
use App\Models\AboutParagraph;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\BlogPostSection;
use App\Models\HomeFeatureItem;
use App\Models\HomeHighlight;
use App\Models\HomeServiceCard;
use App\Models\HomeStat;
use App\Models\HomeTestimonial;
use App\Models\NavigationItem;
use App\Models\OfficeLocation;
use App\Models\PartnerLogo;
use App\Models\ServiceGroup;
use App\Models\ServiceItem;
use App\Models\SiteSetting;
use App\Models\SocialLink;
use App\Observers\AboutPageCacheObserver;
use App\Observers\HomePageCacheObserver;
use App\Observers\ProjectsCacheObserver;
use App\Observers\ServicesPageCacheObserver;
use App\Observers\SiteLayoutCacheObserver;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind('inertia.view-finder', function ($app) {
            return new DomainPageFinder(
                $app['files'],
                $app['config']->get('inertia.pages.extensions'),
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->registerSiteLayoutObservers();
        $this->configureFortifyPasswordResetThrottling();
    }

    protected function configureFortifyPasswordResetThrottling(): void
    {
        $this->app->booted(function (): void {
            $router = $this->app->make('router');

            foreach ($router->getRoutes() as $route) {
                if (in_array($route->getName(), ['password.email', 'password.update'], true)) {
                    $route->middleware('throttle:password-reset');
                }
            }
        });
    }

    protected function registerSiteLayoutObservers(): void
    {
        $siteLayoutObserver = SiteLayoutCacheObserver::class;
        $homePageObserver = HomePageCacheObserver::class;
        $aboutPageObserver = AboutPageCacheObserver::class;
        $servicesPageObserver = ServicesPageCacheObserver::class;
        $projectsPageObserver = ProjectsCacheObserver::class;

        NavigationItem::observe($siteLayoutObserver);
        PartnerLogo::observe($siteLayoutObserver);
        OfficeLocation::observe($siteLayoutObserver);
        SocialLink::observe($siteLayoutObserver);
        SiteSetting::observe($siteLayoutObserver);

        HomeStat::observe($homePageObserver);
        HomeHighlight::observe($homePageObserver);
        HomeFeatureItem::observe($homePageObserver);
        HomeTestimonial::observe($homePageObserver);
        HomeServiceCard::observe($homePageObserver);
        SiteSetting::observe($homePageObserver);

        AboutParagraph::observe($aboutPageObserver);
        AboutCoreValue::observe($aboutPageObserver);
        AboutJourneyMilestone::observe($aboutPageObserver);
        SiteSetting::observe($aboutPageObserver);

        ServiceGroup::observe($servicesPageObserver);
        ServiceItem::observe($servicesPageObserver);
        SiteSetting::observe($servicesPageObserver);

        BlogPost::observe($projectsPageObserver);
        BlogPostSection::observe($projectsPageObserver);
        BlogCategory::observe($projectsPageObserver);
        SiteSetting::observe($projectsPageObserver);
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        if (str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
