<?php

namespace App\Http\Controllers\Teacher;

use App\Enums\AttendanceStatus;
use App\Exceptions\AttendanceException;
use App\Http\Controllers\Controller;
use App\Models\AttendanceSession;
use App\Models\Batch;
use App\Models\BatchEnrollment;
use App\Models\LeaveRequest;
use App\Models\User;
use App\Services\AttendanceService;
use App\Services\AttendanceTokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AttendanceController extends Controller
{
    public function __construct(
        private readonly AttendanceService $attendance,
        private readonly AttendanceTokenService $tokens,
    ) {}

    public function index(): Response
    {
        $teacher = $this->teacher();

        $batches = Batch::query()
            ->where('teacher_id', $teacher->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(fn (Batch $batch): array => [
                'id' => $batch->id,
                'name' => $batch->name,
                'level' => $batch->level->value,
                'students' => BatchEnrollment::query()->where('batch_id', $batch->id)->count(),
                'open_session_id' => AttendanceSession::query()
                    ->where('batch_id', $batch->id)
                    ->whereNull('closed_at')
                    ->get()
                    ->first(fn (AttendanceSession $s): bool => $s->isOpen())?->id,
            ]);

        $recent = AttendanceSession::query()
            ->whereIn('batch_id', $batches->pluck('id'))
            ->with('batch:id,name')
            ->withCount('records as present_count')
            ->latest('started_at')
            ->limit(15)
            ->get()
            ->map(fn (AttendanceSession $session): array => [
                'id' => $session->id,
                'batch' => $session->batch->name,
                'started_at' => $session->started_at->toIso8601String(),
                'open' => $session->isOpen(),
                'present_count' => $session->present_count,
            ]);

        $leaveRequests = LeaveRequest::query()
            ->whereIn('batch_id', $batches->pluck('id'))
            ->with(['student:id,name', 'batch:id,name'])
            ->orderByRaw("case when status = 'pending' then 0 else 1 end")
            ->latest('id')
            ->limit(30)
            ->get()
            ->map(fn (LeaveRequest $leave): array => [
                'id' => $leave->id,
                'student' => $leave->student->name,
                'batch' => $leave->batch->name,
                'from_date' => $leave->from_date->toDateString(),
                'to_date' => $leave->to_date->toDateString(),
                'reason' => $leave->reason,
                'status' => $leave->status->value,
                'decision_note' => $leave->decision_note,
            ]);

        return Inertia::render('app/teacher/attendance/index', [
            'batches' => $batches,
            'recent' => $recent,
            'leaveRequests' => $leaveRequests,
        ]);
    }

    public function store(Batch $batch): RedirectResponse
    {
        abort_unless($batch->teacher_id === $this->teacher()->id, 404);

        $session = $this->attendance->open($batch, $this->teacher());

        return to_route('teacher.attendance.show', $session);
    }

    public function show(AttendanceSession $session): Response
    {
        $this->authorizeSession($session);
        $session->load('batch:id,name,level');

        return Inertia::render('app/teacher/attendance/show', [
            'session' => [
                'id' => $session->id,
                'batch' => $session->batch->name,
                'started_at' => $session->started_at->toIso8601String(),
                'open' => $session->isOpen(),
            ],
            'live' => $this->live($session),
        ]);
    }

    public function live(AttendanceSession $session): JsonResponse|array
    {
        $this->authorizeSession($session);

        $roster = $this->attendance->roster($session);

        $payload = [
            'open' => $session->isOpen(),
            'token' => $session->isOpen() ? $this->tokens->current($session) : null,
            'roster' => $roster->all(),
            'present_count' => $roster->where('status', AttendanceStatus::Present->value)->count(),
            'excused_count' => $roster->where('status', AttendanceStatus::Excused->value)->count(),
            'total' => $roster->count(),
        ];

        return request()->expectsJson() ? response()->json($payload) : $payload;
    }

    public function update(Request $request, AttendanceSession $session, User $student): RedirectResponse|JsonResponse
    {
        $this->authorizeSession($session);

        $validated = $request->validate([
            'status' => ['required', Rule::in(['present', 'excused', 'absent'])],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $this->attendance->setStatus(
                $session,
                $student,
                $validated['status'] === 'absent' ? null : AttendanceStatus::from($validated['status']),
                $this->teacher(),
                $validated['reason'] ?? null,
            );
        } catch (AttendanceException $exception) {
            abort(422, $exception->getMessage());
        }

        return $request->expectsJson() ? response()->json(['ok' => true]) : back();
    }

    public function close(AttendanceSession $session): RedirectResponse
    {
        $this->authorizeSession($session);

        $this->attendance->close($session);

        return to_route('teacher.attendance.index');
    }

    private function teacher(): User
    {
        /** @var User $user */
        $user = Auth::user();

        return $user;
    }

    private function authorizeSession(AttendanceSession $session): void
    {
        abort_unless($session->batch()->where('teacher_id', $this->teacher()->id)->exists(), 404);
    }
}
