<?php

namespace App\Http\Controllers;

use App\Services\ServicesPageService;
use Inertia\Inertia;
use Inertia\Response;

class ServicesController extends Controller
{
    public function index(ServicesPageService $servicesPageService): Response
    {
        return Inertia::render('marketing/services/index', [
            'servicesPage' => $servicesPageService->toInertiaProps(),
        ]);
    }
}
