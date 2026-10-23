<?php

namespace App\Http\Controllers\Student;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\PlacementAccessRequested;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Inertia\Inertia;

class PlacementRequestController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $profile = $request->user()->studentProfile;

        abort_if($profile === null || $profile->wantsPlacement(), 422, 'You are already registered for placement.');

        if ($profile->placement_requested_at === null) {
            $profile->update(['placement_requested_at' => now()]);

            Notification::send(
                User::query()->whereIn('role', UserRole::placementStaff())->get(),
                new PlacementAccessRequested($profile),
            );
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Request sent. BV will get back to you.')]);

        return back();
    }
}
