<?php

namespace App\Http\Controllers\Student;

use App\Exceptions\AttendanceException;
use App\Http\Controllers\Controller;
use App\Models\LeaveRequest;
use App\Models\User;
use App\Services\AttendanceService;
use App\Services\AttendanceSummaryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AttendanceController extends Controller
{
    public function index(Request $request, AttendanceSummaryService $summary): Response
    {
        /** @var User $student */
        $student = $request->user();

        $leaves = LeaveRequest::query()
            ->where('student_id', $student->id)
            ->latest('id')
            ->limit(10)
            ->get()
            ->map(fn (LeaveRequest $leave): array => [
                'id' => $leave->id,
                'from_date' => $leave->from_date->toDateString(),
                'to_date' => $leave->to_date->toDateString(),
                'reason' => $leave->reason,
                'status' => $leave->status->value,
                'decision_note' => $leave->decision_note,
            ]);

        return Inertia::render('app/student/attendance', [
            'summary' => $summary->forStudent($student),
            'leaves' => $leaves,
        ]);
    }

    public function scan(Request $request, AttendanceService $attendance): JsonResponse
    {
        $validated = $request->validate(['token' => ['required', 'string', 'max:200']]);

        /** @var User $student */
        $student = $request->user();

        try {
            $result = $attendance->markByToken($student, $validated['token']);
        } catch (AttendanceException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'result' => $result,
            'message' => $result === 'marked' ? 'You are marked present.' : 'You were already marked present.',
        ]);
    }
}
