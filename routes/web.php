<?php

use App\Http\Controllers\AboutController;
use App\Http\Controllers\ActivationCodeController;
use App\Http\Controllers\Auth\StudentRegistrationController;
use App\Http\Controllers\Auth\TrainingManagerLoginController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\InquiryController;
use App\Http\Controllers\ProjectsController;
use App\Http\Controllers\Recruiter\BatchTransferController;
use App\Http\Controllers\Recruiter\StudentProfileController as RecruiterStudentProfileController;
use App\Http\Controllers\RegistrationPaymentController;
use App\Http\Controllers\RobotsController;
use App\Http\Controllers\ServicesController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\Student\ClassController;
use App\Http\Controllers\Student\CvController;
use App\Http\Controllers\Student\NotificationController;
use App\Http\Controllers\Student\PdfController;
use App\Http\Controllers\Teacher\MaterialController as TeacherMaterialController;
use App\Http\Controllers\TrainingManager\BatchController as TrainingManagerBatchController;
use App\Http\Controllers\TrainingManager\BatchEnrollmentController as TrainingManagerBatchEnrollmentController;
use App\Http\Controllers\TrainingManager\DashboardController as TrainingManagerDashboardController;
use App\Http\Controllers\TrainingManager\LibraryController as TrainingManagerLibraryController;
use App\Http\Controllers\TrainingManager\TrainerController as TrainingManagerTrainerController;
use Illuminate\Support\Facades\Route;

Route::get('/robots.txt', RobotsController::class)->name('robots');
Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/about', [AboutController::class, 'index'])->name('about');
Route::redirect('/projects', '/success-stories', 301);
Route::get('/projects/{slug}', function (string $slug) {
    return redirect("/success-stories/{$slug}", 301);
})->where('slug', '.*');
Route::get('/success-stories', [ProjectsController::class, 'index'])->name('projects');
Route::get('/success-stories/{slug}', [ProjectsController::class, 'show'])->name('projects.show');

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

Route::get('/training-manager', TrainingManagerLoginController::class)
    ->name('training-manager.login');

Route::post('/webhooks/cashfree', [RegistrationPaymentController::class, 'webhook'])
    ->name('webhooks.cashfree');

Route::match(['get', 'post'], '/payments/registration/callback', [RegistrationPaymentController::class, 'callback'])
    ->name('payments.registration.callback');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'app/dashboard')->name('dashboard');

    Route::post('/payments/registration/order', [RegistrationPaymentController::class, 'createOrder'])
        ->middleware('throttle:10,1')
        ->name('payments.registration.order');
    Route::post('/payments/registration/verify', [RegistrationPaymentController::class, 'verify'])
        ->middleware('throttle:20,1')
        ->name('payments.registration.verify');

    Route::post('/activation-codes/redeem', [ActivationCodeController::class, 'redeem'])
        ->middleware('throttle:10,1')
        ->name('activation-codes.redeem');

    Route::middleware(['role:student', 'student.paid'])->prefix('student')->name('student.')->group(function () {
        Route::get('/cv', [CvController::class, 'edit'])->name('cv.edit');
        Route::post('/cv', [CvController::class, 'update'])->name('cv.update');
        Route::post('/cv/translate', [CvController::class, 'translate'])->name('cv.translate');
        Route::get('/classes', [ClassController::class, 'index'])->name('classes.index');
        Route::get('/pdfs', [PdfController::class, 'index'])->name('pdfs.index');
        Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
        Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])
            ->name('notifications.read-all');
        Route::post('/notifications/{notification}/read', [NotificationController::class, 'markAsRead'])
            ->name('notifications.read');
    });

    Route::middleware(['role:recruiter,employee,admin,teacher'])->prefix('recruiter')->name('recruiter.')->group(function () {
        Route::get('/students', [RecruiterStudentProfileController::class, 'index'])->name('students.index');
        Route::get('/students/{studentProfile}', [RecruiterStudentProfileController::class, 'show'])->name('students.show');
        Route::post('/students/{studentProfile}/cv/translate', [RecruiterStudentProfileController::class, 'translate'])->name('students.cv.translate');
        Route::post('/students/{studentProfile}/transfer', [BatchTransferController::class, 'store'])->name('students.transfer');
    });

    Route::middleware(['role:teacher'])->prefix('teacher')->name('teacher.')->group(function () {
        Route::get('/materials', [TeacherMaterialController::class, 'index'])->name('materials.index');
        Route::get('/materials/folders/{folder}', [TeacherMaterialController::class, 'showFolder'])->name('materials.folders.show');
        Route::post('/materials/folders/{folder}/share', [TeacherMaterialController::class, 'shareFolder'])->name('materials.folders.share');
        Route::post('/materials/files/{file}/share', [TeacherMaterialController::class, 'shareFile'])->name('materials.files.share');
        Route::get('/materials/files/{file}/download', [TeacherMaterialController::class, 'download'])->name('materials.files.download');
    });

    Route::middleware(['role:training_manager'])->prefix('training-manager')->name('training-manager.')->group(function () {
        Route::get('/dashboard', TrainingManagerDashboardController::class)->name('dashboard');

        Route::get('/trainers', [TrainingManagerTrainerController::class, 'index'])->name('trainers.index');
        Route::get('/trainers/create', [TrainingManagerTrainerController::class, 'create'])->name('trainers.create');
        Route::post('/trainers', [TrainingManagerTrainerController::class, 'store'])->name('trainers.store');
        Route::get('/trainers/{trainer}/edit', [TrainingManagerTrainerController::class, 'edit'])->name('trainers.edit');
        Route::put('/trainers/{trainer}', [TrainingManagerTrainerController::class, 'update'])->name('trainers.update');
        Route::delete('/trainers/{trainer}', [TrainingManagerTrainerController::class, 'destroy'])->name('trainers.destroy');

        Route::get('/batches', [TrainingManagerBatchController::class, 'index'])->name('batches.index');
        Route::get('/batches/create', [TrainingManagerBatchController::class, 'create'])->name('batches.create');
        Route::post('/batches', [TrainingManagerBatchController::class, 'store'])->name('batches.store');
        Route::get('/batches/enrollment-template', [TrainingManagerBatchEnrollmentController::class, 'downloadTemplate'])->name('batches.enrollment-template');
        Route::get('/batches/unenrolled-candidates', [TrainingManagerBatchEnrollmentController::class, 'searchCandidates'])->name('batches.unenrolled-candidates');
        Route::get('/batches/{batch}/students', [TrainingManagerBatchEnrollmentController::class, 'students'])->name('batches.students');
        Route::post('/batches/{batch}/enrollments', [TrainingManagerBatchEnrollmentController::class, 'enroll'])->name('batches.enrollments.store');
        Route::post('/batches/{batch}/enrollments/import', [TrainingManagerBatchEnrollmentController::class, 'import'])->name('batches.enrollments.import');
        Route::delete('/batches/{batch}/enrollments/{enrollment}', [TrainingManagerBatchEnrollmentController::class, 'destroy'])->name('batches.enrollments.destroy');
        Route::get('/batches/{batch}/edit', [TrainingManagerBatchController::class, 'edit'])->name('batches.edit');
        Route::put('/batches/{batch}', [TrainingManagerBatchController::class, 'update'])->name('batches.update');
        Route::patch('/batches/{batch}/active', [TrainingManagerBatchController::class, 'toggleActive'])->name('batches.active');
        Route::delete('/batches/{batch}', [TrainingManagerBatchController::class, 'destroy'])->name('batches.destroy');

        Route::get('/library', [TrainingManagerLibraryController::class, 'index'])->name('library.index');
        Route::post('/library/folders', [TrainingManagerLibraryController::class, 'storeFolder'])->name('library.folders.store');
        Route::delete('/library/folders/{folder}', [TrainingManagerLibraryController::class, 'destroyFolder'])->name('library.folders.destroy');
        Route::post('/library/folders/{folder}/move', [TrainingManagerLibraryController::class, 'moveFolder'])->name('library.folders.move');
        Route::post('/library/folders/{folder}/copy', [TrainingManagerLibraryController::class, 'copyFolder'])->name('library.folders.copy');
        Route::post('/library/folders/{folder}/files', [TrainingManagerLibraryController::class, 'storeFile'])->name('library.files.store');
        Route::post('/library/folders/{folder}/youtube', [TrainingManagerLibraryController::class, 'storeYoutube'])->name('library.youtube.store');
        Route::post('/library/folders/{folder}/links', [TrainingManagerLibraryController::class, 'storeLink'])->name('library.links.store');
        Route::put('/library/files/{file}', [TrainingManagerLibraryController::class, 'renameFile'])->name('library.files.rename');
        Route::post('/library/files/{file}/move', [TrainingManagerLibraryController::class, 'moveFile'])->name('library.files.move');
        Route::post('/library/files/{file}/copy', [TrainingManagerLibraryController::class, 'copyFile'])->name('library.files.copy');
        Route::delete('/library/files/{file}', [TrainingManagerLibraryController::class, 'destroyFile'])->name('library.files.destroy');
        Route::post('/library/folders/{folder}/assign', [TrainingManagerLibraryController::class, 'assignFolder'])->name('library.folders.assign');
        Route::post('/library/files/{file}/assign', [TrainingManagerLibraryController::class, 'assignFile'])->name('library.files.assign');
        Route::post('/library/folders/{folder}/share', [TrainingManagerLibraryController::class, 'shareFolder'])->name('library.folders.share');
        Route::post('/library/files/{file}/share', [TrainingManagerLibraryController::class, 'shareFile'])->name('library.files.share');
        Route::get('/library/files/{file}/download', [TrainingManagerLibraryController::class, 'download'])->name('library.files.download');
    });
});

require __DIR__.'/settings.php';
