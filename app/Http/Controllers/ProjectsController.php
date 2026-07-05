<?php

namespace App\Http\Controllers;

use App\Services\ProjectsPageService;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ProjectsController extends Controller
{
    public function index(ProjectsPageService $projectsPageService): Response
    {
        return Inertia::render('marketing/projects', [
            'projectsPage' => $projectsPageService->toIndexProps(),
        ]);
    }

    public function show(string $slug, ProjectsPageService $projectsPageService): Response
    {
        $payload = $projectsPageService->toShowProps($slug);

        if ($payload === null) {
            throw new NotFoundHttpException;
        }

        return Inertia::render('marketing/project-show', $payload);
    }
}
