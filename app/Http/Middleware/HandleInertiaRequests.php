<?php

namespace App\Http\Middleware;

use App\Services\SiteLayoutService;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'appUrl' => config('app.url'),
            'seo' => [
                'siteName' => config('seo.site_name'),
                'defaultDescription' => config('seo.default_description'),
                'defaultOgImage' => config('seo.default_og_image'),
                'twitterHandle' => config('seo.twitter_handle'),
            ],
            'auth' => [
                'user' => $request->user()?->toAuthArray(),
            ],
            'razorpayKey' => config('razorpay.key'),
            'registrationFeeAmount' => (int) config('razorpay.registration_fee_amount'),
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'siteLayout' => app(SiteLayoutService::class)->toInertiaProps(),
        ];
    }
}
