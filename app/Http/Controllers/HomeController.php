<?php

namespace App\Http\Controllers;

use App\Services\HomePageService;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function index(HomePageService $homePageService): Response
    {
        return Inertia::render('marketing/home', [
            'homePage' => $homePageService->toInertiaProps(),
        ]);
    }
}
