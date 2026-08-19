<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Fortify\Features;

class TrainingManagerLoginController extends Controller
{
    public function __invoke(Request $request): Response|RedirectResponse
    {
        $user = $request->user();

        if ($user !== null) {
            if ($user->isTrainingManager()) {
                return redirect()->route('training-manager.dashboard');
            }

            abort(403);
        }

        return Inertia::render('auth/training-manager-login', [
            'canResetPassword' => Features::enabled(Features::resetPasswords()),
            'status' => $request->session()->get('status'),
        ]);
    }
}
