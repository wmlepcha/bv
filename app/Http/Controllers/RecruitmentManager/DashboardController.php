<?php

namespace App\Http\Controllers\RecruitmentManager;

use App\Http\Controllers\Controller;
use App\Models\Inquiry;
use App\Models\StudentProfile;
use App\Support\PlacementDashboardPresenter;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(PlacementDashboardPresenter $presenter): Response
    {
        $profiles = StudentProfile::query()
            ->registeredForPlacement()
            ->with('user.batchEnrollment.batch')
            ->join('users', 'users.id', '=', 'student_profiles.user_id')
            ->orderBy('users.name')
            ->select('student_profiles.*')
            ->paginate(25);

        return Inertia::render('app/recruitment-manager/dashboard', [
            'counts' => $presenter->counts(),
            'newInquiries' => Inquiry::query()->where('created_at', '>=', now()->subDays(7))->count(),
            'heatmap' => [
                'rows' => $presenter->heatmapRows($profiles->getCollection()),
                'links' => $profiles->linkCollection(),
                'total' => $profiles->total(),
            ],
        ]);
    }
}
