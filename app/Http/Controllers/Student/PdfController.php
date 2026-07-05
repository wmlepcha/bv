<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class PdfController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('app/student/pdfs');
    }
}
