<?php

use App\Http\Controllers\AboutController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProjectsController;
use App\Http\Controllers\ActivationCodeController;
use App\Http\Controllers\Auth\StudentRegistrationController;
use App\Http\Controllers\InquiryController;
use App\Http\Controllers\Recruiter\StudentProfileController as RecruiterStudentProfileController;
use App\Http\Controllers\RegistrationPaymentController;
use App\Http\Controllers\RobotsController;
use App\Http\Controllers\ServicesController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\Student\ClassController;
use App\Http\Controllers\Student\NotificationController;
use App\Http\Controllers\Student\PdfController;
use Illuminate\Support\Facades\Route;

Route::get('/robots.txt', RobotsController::class)->name('robots');
Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/about', [AboutController::class, 'index'])->name('about');
Route::get('/projects', [ProjectsController::class, 'index'])->name('projects');
Route::get('/projects/{slug}', [ProjectsController::class, 'show'])->name('projects.show');

Route::post('/inquiries', [InquiryController::class, 'store'])
    ->middleware('throttle:5,1')
    ->name('inquiries.store');

Route::get('/services', [ServicesController::class, 'index'])->name('services.index');
Route::redirect('/services/language-training', '/services#courses')->name('services.language');
Route::redirect('/services/placement', '/services#placement-integration')->name('services.placement');
Route::redirect('/services/migration', '/services#placement-integration')->name('services.migration');

Route::middleware('guest')->group(function () {
    Route::get('/student/register', [StudentRegistrationController::class, 'create'])
        ->name('student.register.create');
    Route::post('/student/register', [StudentRegistrationController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('student.register.store');
});

Route::post('/webhooks/razorpay', [RegistrationPaymentController::class, 'webhook'])
    ->name('webhooks.razorpay');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'app/dashboard')->name('dashboard');

    Route::post('/payments/registration/order', [RegistrationPaymentController::class, 'createOrder'])
        ->name('payments.registration.order');
    Route::post('/payments/registration/verify', [RegistrationPaymentController::class, 'verify'])
        ->name('payments.registration.verify');

    Route::post('/activation-codes/redeem', [ActivationCodeController::class, 'redeem'])
        ->name('activation-codes.redeem');

    Route::middleware(['role:student', 'student.paid'])->prefix('student')->name('student.')->group(function () {
        Route::get('/classes', [ClassController::class, 'index'])->name('classes.index');
        Route::get('/pdfs', [PdfController::class, 'index'])->name('pdfs.index');
        Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    });

    Route::middleware(['role:recruiter,employee,admin'])->prefix('recruiter')->name('recruiter.')->group(function () {
        Route::get('/students', [RecruiterStudentProfileController::class, 'index'])->name('students.index');
        Route::get('/students/{studentProfile}', [RecruiterStudentProfileController::class, 'show'])->name('students.show');
    });
});

require __DIR__.'/settings.php';
