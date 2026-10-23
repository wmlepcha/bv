<?php

namespace App\Http\Controllers\TrainingManager;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\TrainingManager\StoreTrainerRequest;
use App\Http\Requests\TrainingManager\UpdateTrainerRequest;
use App\Models\BatchEnrollment;
use App\Models\User;
use App\Notifications\WelcomeWithCredentials;
use App\Support\TrainerPresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class TrainerController extends Controller
{
    public function index(): Response
    {
        $trainers = User::query()
            ->where('role', UserRole::Teacher)
            ->withCount(['teachingBatches', 'createdAssignments'])
            ->selectSub(
                BatchEnrollment::query()
                    ->selectRaw('count(distinct batch_enrollments.student_id)')
                    ->join('batches', 'batches.id', '=', 'batch_enrollments.batch_id')
                    ->whereColumn('batches.teacher_id', 'users.id'),
                'students_count',
            )
            ->with(['teachingBatches' => fn ($query) => $query
                ->select('id', 'teacher_id', 'name', 'ends_on', 'is_active')
                ->orderByDesc('is_active')
                ->orderBy('ends_on')])
            ->orderBy('name')
            ->get()
            ->map(fn (User $trainer): array => TrainerPresenter::forIndex($trainer));

        return Inertia::render('app/training-manager/trainers/index', [
            'trainers' => $trainers,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('app/training-manager/trainers/create');
    }

    public function store(StoreTrainerRequest $request): RedirectResponse
    {
        $user = User::create([
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'password' => $request->validated('password'),
        ]);

        $user->forceFill([
            'role' => UserRole::Teacher,
            'registration_paid_at' => now(),
            'email_verified_at' => now(),
        ])->save();

        if ($request->hasFile('avatar')) {
            $user->forceFill([
                'avatar_path' => $this->storeAvatar(
                    $request->file('avatar'),
                    $user->id,
                    null,
                ),
            ])->save();
        }

        $user->notify(new WelcomeWithCredentials(
            plainPassword: (string) $request->validated('password'),
            role: UserRole::Teacher,
        ));

        return redirect()
            ->route('training-manager.trainers.index')
            ->with('success', 'Trainer created.');
    }

    public function show(User $trainer): Response
    {
        abort_unless($trainer->isTeacher(), 404);

        $trainer->loadCount('teachingBatches');

        return Inertia::render(
            'app/training-manager/trainers/show',
            TrainerPresenter::show($trainer),
        );
    }

    public function edit(User $trainer): Response
    {
        abort_unless($trainer->isTeacher(), 404);

        return Inertia::render('app/training-manager/trainers/edit', [
            'trainer' => [
                'id' => $trainer->id,
                'name' => $trainer->name,
                'email' => $trainer->email,
                'photo_url' => TrainerPresenter::photoUrl($trainer->avatar_path),
            ],
        ]);
    }

    public function update(UpdateTrainerRequest $request, User $trainer): RedirectResponse
    {
        abort_unless($trainer->isTeacher(), 404);

        $trainer->fill([
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
        ]);

        if ($request->filled('password')) {
            $trainer->password = $request->validated('password');
        }

        $avatarPath = $trainer->avatar_path;

        if ($request->hasFile('avatar')) {
            $avatarPath = $this->storeAvatar(
                $request->file('avatar'),
                $trainer->id,
                $avatarPath,
            );
        } elseif ($request->boolean('remove_avatar') && filled($avatarPath)) {
            Storage::disk('public')->delete($avatarPath);
            $avatarPath = null;
        }

        $trainer->avatar_path = $avatarPath;
        $trainer->save();

        return redirect()
            ->route('training-manager.trainers.index')
            ->with('success', 'Trainer updated.');
    }

    public function destroy(User $trainer): RedirectResponse
    {
        abort_unless($trainer->isTeacher(), 404);

        if ($trainer->teachingBatches()->exists()) {
            return redirect()
                ->route('training-manager.trainers.index')
                ->with('error', 'Reassign or delete this trainer’s batches before deleting the trainer.');
        }

        if (filled($trainer->avatar_path)) {
            Storage::disk('public')->delete($trainer->avatar_path);
        }

        $trainer->delete();

        return redirect()
            ->route('training-manager.trainers.index')
            ->with('success', 'Trainer deleted.');
    }

    private function storeAvatar(UploadedFile $file, int $userId, ?string $previous): string
    {
        if (filled($previous)) {
            Storage::disk('public')->delete($previous);
        }

        return $file->store("trainers/{$userId}", 'public');
    }
}
