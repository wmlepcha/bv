<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Fortify\Features;

class AuthPortalController extends Controller
{
    public function login(Request $request): Response|RedirectResponse
    {
        $portal = $request->query('portal');

        if (! in_array($portal, ['student', 'staff', 'teacher'], true)) {
            return Inertia::render('auth/portal');
        }

        return Inertia::render('auth/login', [
            'portal' => $portal,
            'canResetPassword' => Features::enabled(Features::resetPasswords()),
            'status' => $request->session()->get('status'),
        ]);
    }
}
