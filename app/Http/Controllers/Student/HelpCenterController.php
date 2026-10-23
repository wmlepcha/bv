<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\StoreCourseFeedbackRequest;
use App\Services\CourseFeedbackService;
use App\Support\CourseFeedbackPresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HelpCenterController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('app/student/help-center', CourseFeedbackPresenter::forStudent($request->user()));
    }

    public function storeFeedback(StoreCourseFeedbackRequest $request, CourseFeedbackService $feedback): RedirectResponse
    {
        $feedback->submit($request->user(), $request->validated());

        return back()->with('success', 'Thank you! Your feedback has been submitted.');
    }
}
