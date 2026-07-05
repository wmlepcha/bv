<?php

namespace App\Http\Controllers\Recruiter;

use App\Http\Controllers\Controller;
use App\Models\StudentProfile;
use Inertia\Inertia;
use Inertia\Response;

class StudentProfileController extends Controller
{
    public function index(): Response
    {
        $profiles = StudentProfile::query()
            ->with('user:id,name,email,student_number')
            ->latest()
            ->get()
            ->map(fn (StudentProfile $profile) => [
                'id' => $profile->id,
                'name' => $profile->user->name,
                'email' => $profile->user->email,
                'student_number' => $profile->user->student_number,
                'headline' => $profile->headline,
            ]);

        return Inertia::render('app/recruiter/students/index', [
            'profiles' => $profiles,
        ]);
    }

    public function show(StudentProfile $studentProfile): Response
    {
        $studentProfile->load('user:id,name,email,student_number');

        return Inertia::render('app/recruiter/students/show', [
            'profile' => [
                'id' => $studentProfile->id,
                'name' => $studentProfile->user->name,
                'email' => $studentProfile->user->email,
                'student_number' => $studentProfile->user->student_number,
                'headline' => $studentProfile->headline,
                'languages' => $studentProfile->languages ?? [],
                'proficiency_summary' => $studentProfile->proficiency_summary,
                'vitae_data' => $studentProfile->vitae_data ?? [],
            ],
        ]);
    }
}
