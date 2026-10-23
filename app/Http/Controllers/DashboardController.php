<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\RecruiterDashboardPresenter;
use App\Support\StudentDashboardPresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request, StudentDashboardPresenter $presenter, RecruiterDashboardPresenter $recruiterPresenter): Response|RedirectResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        if ($user->isSalesTeam()) {
            return redirect('/bv-admin');
        }

        if ($user->isRecruitmentManager()) {
            return redirect()->route('recruitment-manager.dashboard');
        }

        if ($user->isStudent()) {
            return Inertia::render(
                'app/student/dashboard',
                $presenter->present($user),
            );
        }

        if ($user->isRecruiter() || $user->isEmployee() || $user->isAdmin()) {
            return Inertia::render('app/recruiter/dashboard', $recruiterPresenter->present());
        }

        return Inertia::render('app/dashboard');
    }
}
