# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

@AGENTS.md

The Laravel Boost guidelines imported above (package versions, Boost MCP tools, PHPUnit-only testing, Pint, `search-docs`-before-changes) apply in full. Domain skills live in `.cursor/skills/` (fortify, inertia-react, laravel-best-practices, tailwindcss, wayfinder).

## Commands

```bash
composer dev                      # php artisan dev — runs server, queue, logs, vite together
npm run dev                       # Vite only (pinned to 127.0.0.1:5173, strictPort)
npm run build                     # production assets (needed if tests hit a Vite manifest error)

php artisan test --compact                                   # all PHP tests
php artisan test --compact tests/Feature/Batches/SomeTest.php
php artisan test --compact --filter=test_method_name

vendor/bin/pint --dirty --format agent   # PHP formatting (run after PHP edits)
composer types:check                     # PHPStan / Larastan, level 7
npm run lint                             # ESLint --fix
npm run format                           # Prettier on resources/
npm run types:check                      # tsc --noEmit
composer ci:check                        # everything CI runs
```

Tests use in-memory SQLite (see `phpunit.xml`); CI runs `npm run build` before tests, on PHP 8.3–8.5.

## Architecture

**Bilingual Vitae** is a language-training / overseas-placement business: a public marketing site plus a role-based learning portal plus a Filament admin.

### Three surfaces

1. **Marketing site** (`/`, `/about`, `/services`, `/success-stories`) — Inertia pages whose content is fully CMS-driven from Filament. Each page has a `*PageService` in `app/Services` that builds and caches (`Cache::remember`) the payload; matching observers in `app/Observers` (registered in `AppServiceProvider::registerSiteLayoutObservers`) bust the cache when any contributing model changes. **When adding a model that feeds a marketing page, register it with the right observer or edits won't show.**
2. **Portal** (Inertia + React, `routes/web.php`) — role-gated route groups: `student`, `recruiter` (shared with employee/admin/teacher), `teacher`, `training_manager`. Roles are `App\Enums\UserRole`; gating uses the `role:` middleware alias (`EnsureUserHasRole`). Student content routes also require `student.paid` (`EnsureStudentRegistrationPaid` → `User::canAccessDashboard()`), i.e. a paid registration fee or redeemed activation code.
3. **Filament admin** at `/bv-admin` (`app/Providers/Filament/AdminPanelProvider.php`) — CMS content, candidates, users, payments, plans, batches, activation codes. Custom settings pages live in `app/Filament/Pages`, grouped via `app/Filament/Clusters`.

### Frontend is split into domains

`resources/js/{marketing,app,auth,shared}`. Inertia page names are prefixed by domain: `Inertia::render('app/student/classes')` resolves to `resources/js/app/pages/student/classes.tsx`. This resolution is custom on both sides — `app/Inertia/DomainPageFinder.php` (server, bound in `AppServiceProvider`) and `resolvePagePath` in `resources/js/app.tsx` (client, which also assigns layouts by prefix). A new domain must be added to both.

Vite aliases (`vite.config.ts`): `@/components/ui`, `@/lib`, `@/hooks`, `@/types` point into `resources/js/shared/...`; `@app`, `@marketing`, `@auth`, `@shared` point to their domain roots; `@/` is `resources/js`. Wayfinder output (`resources/js/actions`, `resources/js/routes`) is generated — don't hand-edit. Role-based sidebar items are in `resources/js/app/config/navigation.ts`.

Globally shared Inertia props (auth user via `User::toAuthArray()`, flash, site layout, registration plans, Cashfree app id, unread notification count) are defined in `app/Http/Middleware/HandleInertiaRequests.php`.

### Backend conventions

- Controllers stay thin; business logic lives in `app/Services` (e.g. `BatchEnrollmentService`, `MaterialLibraryService`, `AssignmentSubmissionService`), and prop shaping for Inertia lives in `app/Support/*Presenter.php`.
- Controllers are namespaced by role (`Student/`, `Teacher/`, `TrainingManager/`, `Recruiter/`); form requests mirror that under `app/Http/Requests`.
- Notifications go to database + a custom WhatsApp channel (`app/Notifications/Channels/WhatsAppChannel.php`, `WhatsAppNotificationService`); per-student channel selection is in `app/Support/StudentNotificationChannels.php`.
- Payments use Cashfree (webhook `/webhooks/cashfree` and callback are CSRF-exempt in `bootstrap/app.php`); invoices are served via signed URLs.
- Upload-size errors (`PostTooLargeException`) are rendered per-area in `bootstrap/app.php` — update the messages there if upload limits change.
- Dates use `CarbonImmutable` globally; destructive DB commands are prohibited in production.

### Key domain concepts

Batches (with Centers) → BatchEnrollments of students; a Material library (folders/files incl. YouTube videos/playlists and links) managed by training managers and assigned to trainers or shared with batches (shown to students as the "Study Bank"); Assignments (folders, sections, questions, audio/media) authored by teachers/training managers, submitted by students, and graded with annotations; student CVs with translation (`CvTranslationService`) viewed by recruiters.
