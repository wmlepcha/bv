<?php

namespace App\Http\Controllers\Student;

use App\Exceptions\AttendanceException;
use App\Http\Controllers\Controller;
use App\Models\LeaveRequest;
use App\Models\User;
use App\Services\LeaveService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class LeaveRequestController extends Controller
{
    public function store(Request $request, LeaveService $leaves): RedirectResponse
    {
        $validated = $request->validate([
            'from_date' => ['required', 'date'],
            'to_date' => ['required', 'date'],
            'reason' => ['required', 'string', 'min:3', 'max:500'],
        ]);

        /** @var User $student */
        $student = $request->user();

        try {
            $leaves->submit($student, $validated['from_date'], $validated['to_date'], $validated['reason']);
        } catch (AttendanceException $exception) {
            throw ValidationException::withMessages(['from_date' => $exception->getMessage()]);
        }

        return back();
    }

    public function destroy(Request $request, LeaveRequest $leaveRequest, LeaveService $leaves): RedirectResponse
    {
        /** @var User $student */
        $student = $request->user();

        try {
            $leaves->cancel($student, $leaveRequest);
        } catch (AttendanceException $exception) {
            abort(403, $exception->getMessage());
        }

        return back();
    }
}
