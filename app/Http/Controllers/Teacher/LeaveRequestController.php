<?php

namespace App\Http\Controllers\Teacher;

use App\Exceptions\AttendanceException;
use App\Http\Controllers\Controller;
use App\Models\LeaveRequest;
use App\Models\User;
use App\Services\LeaveService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LeaveRequestController extends Controller
{
    public function update(Request $request, LeaveRequest $leaveRequest, LeaveService $leaves): RedirectResponse
    {
        /** @var User $teacher */
        $teacher = $request->user();

        abort_unless($leaveRequest->batch()->where('teacher_id', $teacher->id)->exists(), 404);

        $validated = $request->validate([
            'decision' => ['required', Rule::in(['approve', 'reject'])],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $leaves->decide($leaveRequest, $teacher, $validated['decision'] === 'approve', $validated['note'] ?? null);
        } catch (AttendanceException $exception) {
            abort(422, $exception->getMessage());
        }

        return back();
    }
}
