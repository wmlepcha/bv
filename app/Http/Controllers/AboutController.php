<?php

namespace App\Http\Controllers;

use App\Services\AboutPageService;
use Inertia\Inertia;
use Inertia\Response;

class AboutController extends Controller
{
    public function index(AboutPageService $aboutPageService): Response
    {
        return Inertia::render('marketing/about', [
            'aboutPage' => $aboutPageService->toInertiaProps(),
        ]);
    }
}
