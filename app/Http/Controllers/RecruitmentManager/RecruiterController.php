<?php

namespace App\Http\Controllers\RecruitmentManager;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\RecruitmentManager\StoreRecruiterRequest;
use App\Http\Requests\RecruitmentManager\UpdateRecruiterRequest;
use App\Models\RecruiterProject;
use App\Models\User;
use App\Notifications\WelcomeWithCredentials;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class RecruiterController extends Controller
{
    public function index(): Response
    {
        $recruiters = User::query()
            ->where('role', UserRole::Recruiter)
            ->withCount('recruiterProjects')
            ->with(['recruiterProjects' => fn ($q) => $q
                ->withCount([
                    'entries as students_count',
                    'entries as approved_count' => fn ($entries) => $entries->whereNotNull('approved_at'),
                ])
                ->orderByDesc('id')])
            ->orderBy('name')
            ->get()
            ->map(fn (User $recruiter): array => [
                ...$this->present($recruiter),
                'projects' => $recruiter->recruiterProjects->map(fn (RecruiterProject $project): array => [
                    'id' => $project->id,
                    'name' => $project->name,
                    'country' => $project->country,
                    'sector' => $project->sector,
                    'finalized' => $project->isFinalized(),
                    'students_count' => $project->students_count,
                    'approved_count' => $project->approved_count,
                ])->all(),
            ]);

        return Inertia::render('app/recruitment-manager/recruiters/index', ['recruiters' => $recruiters]);
    }

    public function create(): Response
    {
        return Inertia::render('app/recruitment-manager/recruiters/form', ['recruiter' => null]);
    }

    public function store(StoreRecruiterRequest $request): RedirectResponse
    {
        $user = User::create([
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'password' => $request->validated('password'),
            'whatsapp_number' => $request->validated('whatsapp_number'),
        ]);

        $user->forceFill([
            'role' => UserRole::Recruiter,
            'registration_paid_at' => now(),
            'email_verified_at' => now(),
        ])->save();

        $user->notify(new WelcomeWithCredentials(
            plainPassword: (string) $request->validated('password'),
            role: UserRole::Recruiter,
        ));

        return redirect()->route('recruitment-manager.recruiters.index')->with('success', 'Recruiter created.');
    }

    public function edit(User $recruiter): Response
    {
        abort_unless($recruiter->isRecruiter(), 404);

        return Inertia::render('app/recruitment-manager/recruiters/form', ['recruiter' => $this->present($recruiter)]);
    }

    public function update(UpdateRecruiterRequest $request, User $recruiter): RedirectResponse
    {
        abort_unless($recruiter->isRecruiter(), 404);

        $recruiter->fill([
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'whatsapp_number' => $request->validated('whatsapp_number'),
        ]);

        if ($request->filled('password')) {
            $recruiter->password = $request->validated('password');
        }

        $recruiter->save();

        return redirect()->route('recruitment-manager.recruiters.index')->with('success', 'Recruiter updated.');
    }

    /**
     * @return array<string, mixed>
     */
    private function present(User $recruiter): array
    {
        return [
            'id' => $recruiter->id,
            'name' => $recruiter->name,
            'email' => $recruiter->email,
            'whatsapp_number' => $recruiter->whatsapp_number,
            'projects_count' => $recruiter->recruiter_projects_count ?? 0,
        ];
    }
}
