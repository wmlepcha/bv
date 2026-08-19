<?php

use App\Http\Middleware\EnsureStudentRegistrationPaid;
use App\Http\Middleware\EnsureUserHasRole;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');

        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->alias([
            'role' => EnsureUserHasRole::class,
            'student.paid' => EnsureStudentRegistrationPaid::class,
        ]);

        $middleware->validateCsrfTokens(except: [
            'webhooks/cashfree',
            'payments/registration/callback',
        ]);

        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
            SecurityHeaders::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );

        $exceptions->render(function (PostTooLargeException $e, Request $request): Response {
            $message = 'Your upload is too large. Use smaller files (passport photo max 5MB, full photo max 8MB, documents max 10MB) and try again.';

            if ($request->header('X-Inertia')) {
                return response()->json([
                    'message' => $message,
                    'errors' => [
                        'passport_photo' => $message,
                        'full_body_photo' => $message,
                        'passport_document' => $message,
                        'drivers_license_document' => $message,
                    ],
                ], 422);
            }

            return redirect()
                ->back()
                ->withErrors([
                    'passport_photo' => $message,
                    'full_body_photo' => $message,
                    'passport_document' => $message,
                    'drivers_license_document' => $message,
                ]);
        });
    })->create();
