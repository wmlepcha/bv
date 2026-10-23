<?php

namespace App\Http\Controllers\TrainingManager;

use App\Http\Controllers\Controller;
use App\Models\CourseFeedback;
use App\Support\CourseFeedbackPresenter;
use Inertia\Inertia;
use Inertia\Response;

class CourseFeedbackController extends Controller
{
    public function index(): Response
    {
        $feedback = CourseFeedback::query()
            ->with(['student:id,name', 'batch:id,name', 'teacher:id,name', 'center:id,name'])
            ->latest()
            ->get()
            ->map(fn (CourseFeedback $item): array => CourseFeedbackPresenter::forIndex($item));

        return Inertia::render('app/training-manager/feedback/index', [
            'feedback' => $feedback,
        ]);
    }

    public function show(CourseFeedback $feedback): Response
    {
        return Inertia::render('app/training-manager/feedback/show', CourseFeedbackPresenter::show($feedback));
    }
}
