<?php

namespace App\Http\Middleware;

use App\Models\Plan;
use App\Services\ChatService;
use App\Services\InquiryOptionsService;
use App\Services\SiteLayoutService;
use App\Support\RegistrationTerms;
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
            'cashfreeAppId' => config('cashfree.app_id'),
            'registrationPlans' => Plan::query()
                ->active()
                ->ordered()
                ->get()
                ->map(fn (Plan $plan): array => $plan->toCheckoutArray())
                ->values()
                ->all(),
            'registrationTermsHtml' => RegistrationTerms::html(),
            'studentPhone' => $request->user()?->studentProfile?->mobile_whatsapp,
            'unread_notifications_count' => fn () => $request->user()?->unreadNotifications()->count() ?? 0,
            'unread_messages_count' => fn () => $this->unreadMessagesCount($request),
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
            'siteLayout' => app(SiteLayoutService::class)->toInertiaProps(),
            'inquiryOptions' => [
                'educationLevels' => app(InquiryOptionsService::class)->educationLevels(),
                'turnstileSiteKey' => config('services.turnstile.site_key'),
            ],
        ];
    }

    private function unreadMessagesCount(Request $request): int
    {
        $user = $request->user();

        if ($user === null) {
            return 0;
        }

        $chat = app(ChatService::class);

        if ($user->isStudent()) {
            return $chat->unreadCountForStudent($user);
        }

        if ($user->isTeacher()) {
            return $chat->unreadCountForTeacher($user);
        }

        return 0;
    }
}
