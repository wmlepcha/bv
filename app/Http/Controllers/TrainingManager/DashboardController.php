<?php

namespace App\Http\Controllers\TrainingManager;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Batch;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('app/training-manager/dashboard', [
            'stats' => [
                'trainers' => User::query()->where('role', UserRole::Teacher)->count(),
                'batches' => Batch::query()->count(),
                'active_batches' => Batch::query()->where('is_active', true)->count(),
            ],
        ]);
    }
}
