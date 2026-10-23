<?php

namespace App\Http\Controllers\RecruitmentManager;

use App\Enums\PlacementStatus;
use App\Enums\RegistrationPurpose;
use App\Http\Controllers\Controller;
use App\Http\Requests\RecruitmentManager\UpdateCandidatePlacementRequest;
use App\Models\StudentProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CandidateController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'purpose' => ['nullable', 'string', 'in:'.implode(',', array_column(RegistrationPurpose::cases(), 'value'))],
            'status' => ['nullable', 'string', 'in:'.implode(',', array_column(PlacementStatus::cases(), 'value'))],
            'requested' => ['nullable', 'in:true,false,1,0'],
        ]);

        $profiles = StudentProfile::query()
            ->with('user.batchEnrollment.batch')
            ->when($filters['q'] ?? null, fn ($q, string $term) => $q->whereHas('user', fn ($u) => $u
                ->where('name', 'like', '%'.$term.'%')
                ->orWhere('student_number', 'like', '%'.$term.'%')))
            ->when($filters['purpose'] ?? null, fn ($q, string $purpose) => $q->where('registration_purpose', $purpose))
            ->when($filters['status'] ?? null, fn ($q, string $status) => $q->where('placement_status', $status))
            ->when($request->boolean('requested'), fn ($q) => $q->whereNotNull('placement_requested_at'))
            ->orderByRaw('placement_requested_at is null')
            ->latest('student_profiles.id')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (StudentProfile $profile): array => [
                'id' => $profile->id,
                'name' => $profile->user->name,
                'email' => $profile->user->email,
                'student_number' => $profile->user->student_number,
                'batch' => $profile->user->batchEnrollment?->batch?->name,
                'registration_purpose' => $profile->registration_purpose->value,
                'placement_status' => $profile->placement_status->value,
                'placement_requested' => $profile->placement_requested_at !== null,
            ]);

        return Inertia::render('app/recruitment-manager/candidates/index', [
            'profiles' => $profiles,
            'filters' => [
                'q' => $filters['q'] ?? '',
                'purpose' => $filters['purpose'] ?? '',
                'status' => $filters['status'] ?? '',
                'requested' => $request->boolean('requested'),
            ],
            'purposes' => RegistrationPurpose::options(),
            'statuses' => PlacementStatus::options(),
        ]);
    }

    public function update(UpdateCandidatePlacementRequest $request, StudentProfile $studentProfile): RedirectResponse
    {
        $studentProfile->update($request->validated());

        return back();
    }
}
