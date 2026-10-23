<?php

use App\Http\Controllers\AboutController;
use App\Http\Controllers\ActivationCodeController;
use App\Http\Controllers\Auth\RecruitmentManagerLoginController;
use App\Http\Controllers\Auth\StudentRegistrationController;
use App\Http\Controllers\Auth\TrainingManagerLoginController;
use App\Http\Controllers\ChatAttachmentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Filament\InquiryDocumentController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\InquiryController;
use App\Http\Controllers\PaymentInvoiceDownloadController;
use App\Http\Controllers\ProjectsController;
use App\Http\Controllers\Recruiter\BatchTransferController;
use App\Http\Controllers\Recruiter\ProjectCandidateController as RecruiterProjectCandidateController;
use App\Http\Controllers\Recruiter\ProjectController as RecruiterProjectController;
use App\Http\Controllers\Recruiter\ProjectDocumentController;
use App\Http\Controllers\Recruiter\ProjectRequirementController;
use App\Http\Controllers\Recruiter\ProjectReviewController;
use App\Http\Controllers\Recruiter\StudentProfileController as RecruiterStudentProfileController;
use App\Http\Controllers\RecruitmentManager\CandidateController as RecruitmentManagerCandidateController;
use App\Http\Controllers\RecruitmentManager\DashboardController as RecruitmentManagerDashboardController;
use App\Http\Controllers\RecruitmentManager\InquiryController as RecruitmentManagerInquiryController;
use App\Http\Controllers\RecruitmentManager\ProjectController as RecruitmentManagerProjectController;
use App\Http\Controllers\RecruitmentManager\RecruiterController as RecruitmentManagerRecruiterController;
use App\Http\Controllers\ReferralController;
use App\Http\Controllers\RegistrationPaymentController;
use App\Http\Controllers\RobotsController;
use App\Http\Controllers\ServicesController;
use App\Http\Controllers\SidebarSearchController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\Student\AssignmentController as StudentAssignmentController;
use App\Http\Controllers\Student\AttendanceController as StudentAttendanceController;
use App\Http\Controllers\Student\ChatController as StudentChatController;
use App\Http\Controllers\Student\CvController;
use App\Http\Controllers\Student\HelpCenterController;
use App\Http\Controllers\Student\LearningController;
use App\Http\Controllers\Student\LeaveRequestController as StudentLeaveRequestController;
use App\Http\Controllers\Student\NotificationController;
use App\Http\Controllers\Student\PaymentInvoiceController;
use App\Http\Controllers\Student\PlacementRequestController;
use App\Http\Controllers\Student\StudyBankController;
use App\Http\Controllers\StudentDocumentController;
use App\Http\Controllers\Teacher\AssignmentController as TeacherAssignmentController;
use App\Http\Controllers\Teacher\AttendanceController as TeacherAttendanceController;
use App\Http\Controllers\Teacher\ChatController as TeacherChatController;
use App\Http\Controllers\Teacher\LeaveRequestController as TeacherLeaveRequestController;
use App\Http\Controllers\Teacher\MaterialController as TeacherMaterialController;
use App\Http\Controllers\TrainingManager\AssignmentController as TrainingManagerAssignmentController;
use App\Http\Controllers\TrainingManager\BatchController as TrainingManagerBatchController;
use App\Http\Controllers\TrainingManager\BatchEnrollmentController as TrainingManagerBatchEnrollmentController;
use App\Http\Controllers\TrainingManager\CourseFeedbackController as TrainingManagerCourseFeedbackController;
use App\Http\Controllers\TrainingManager\DashboardController as TrainingManagerDashboardController;
use App\Http\Controllers\TrainingManager\LibraryController as TrainingManagerLibraryController;
use App\Http\Controllers\TrainingManager\TrainerController as TrainingManagerTrainerController;
use App\Models\StudentProfile;
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

Route::get('/referral', [ReferralController::class, 'create'])->name('referral.create');
Route::post('/referral', [ReferralController::class, 'store'])
    ->middleware('throttle:5,1')
    ->name('referral.store');

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

Route::get('/bv-rm-login', RecruitmentManagerLoginController::class)
    ->name('recruitment-manager.login');

Route::post('/webhooks/cashfree', [RegistrationPaymentController::class, 'webhook'])
    ->name('webhooks.cashfree');

Route::match(['get', 'post'], '/payments/registration/callback', [RegistrationPaymentController::class, 'callback'])
    ->name('payments.registration.callback');

Route::get('/invoices/{payment}/download', PaymentInvoiceDownloadController::class)
    ->middleware('signed')
    ->name('invoices.download');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/search', [SidebarSearchController::class, 'index'])->name('search');

    Route::post('/payments/registration/order', [RegistrationPaymentController::class, 'createOrder'])
        ->middleware('throttle:10,1')
        ->name('payments.registration.order');
    Route::post('/payments/registration/verify', [RegistrationPaymentController::class, 'verify'])
        ->middleware('throttle:20,1')
        ->name('payments.registration.verify');

    Route::post('/activation-codes/redeem', [ActivationCodeController::class, 'redeem'])
        ->middleware('throttle:10,1')
        ->name('activation-codes.redeem');

    Route::get('/student-documents/{studentProfile}/{kind}', [StudentDocumentController::class, 'show'])
        ->whereIn('kind', array_keys(StudentProfile::PRIVATE_DOCUMENTS))
        ->name('student-documents.show');
    Route::get('/project-documents/{requirement}', [ProjectDocumentController::class, 'download'])->name('project-documents.download');
    Route::get('/project-documents/{requirement}/view', [ProjectDocumentController::class, 'view'])->name('project-documents.view');
    Route::get('/project-samples/{requirement}', [ProjectDocumentController::class, 'sample'])->name('project-samples.download');
    Route::get('/project-samples/{requirement}/view', [ProjectDocumentController::class, 'sampleView'])->name('project-samples.view');
    Route::get('/chat/attachments/{message}', ChatAttachmentController::class)->name('chat.attachments.show');

    Route::middleware(['role:student'])->prefix('student')->name('student.')->group(function () {
        Route::post('/placement-request', [PlacementRequestController::class, 'store'])
            ->middleware('throttle:5,1')
            ->name('placement-request.store');
    });

    // Everyone who receives in-app notifications shares this page; the route names stay under "student." for the existing links.
    Route::middleware(['role:student,recruiter,employee,admin,recruitment_manager'])->prefix('student')->name('student.')->group(function () {
        Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
        Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])
            ->name('notifications.read-all');
        Route::post('/notifications/{notification}/read', [NotificationController::class, 'markAsRead'])
            ->name('notifications.read');
    });

    Route::middleware(['role:student', 'student.paid'])->prefix('student')->name('student.')->group(function () {
        Route::get('/assignments', [StudentAssignmentController::class, 'index'])->name('assignments.index');
        Route::get('/assignments/{assignment}', [StudentAssignmentController::class, 'show'])->name('assignments.show');
        Route::post('/assignments/{assignment}', [StudentAssignmentController::class, 'submit'])->name('assignments.submit');
        Route::get('/assignments/{assignment}/submission', [StudentAssignmentController::class, 'submission'])->name('assignments.submission');
        Route::get('/assignments/{assignment}/audio', [StudentAssignmentController::class, 'audio'])->name('assignments.audio');
        Route::get('/assignments/{assignment}/questions/{question}/media', [StudentAssignmentController::class, 'media'])->name('assignments.questions.media');

        Route::get('/cv', [CvController::class, 'edit'])->name('cv.edit');
        Route::post('/cv', [CvController::class, 'update'])->name('cv.update');
        Route::post('/cv/translate', [CvController::class, 'translate'])->name('cv.translate');
        Route::get('/my-learning', [LearningController::class, 'index'])->name('my-learning.index');
        Route::post('/my-learning', [LearningController::class, 'store'])->name('my-learning.store');
        Route::delete('/my-learning/{learningItem}', [LearningController::class, 'destroy'])->name('my-learning.destroy');
        Route::patch('/my-learning/{learningItem}/note', [LearningController::class, 'updateNote'])->name('my-learning.note.update');
        Route::get('/study-bank', [StudyBankController::class, 'index'])->name('study-bank.index');
        Route::get('/study-bank/folders/{folder}', [StudyBankController::class, 'showFolder'])->name('study-bank.folders.show');
        Route::get('/study-bank/files/{file}/download', [StudyBankController::class, 'download'])->name('study-bank.files.download');
        Route::get('/study-bank/files/{file}/preview', [StudyBankController::class, 'preview'])->name('study-bank.files.preview');
        Route::get('/study-bank/files/{file}/playlist-items', [StudyBankController::class, 'playlistItems'])->name('study-bank.files.playlist-items');
        Route::get('/payments/{payment}/invoice', PaymentInvoiceController::class)->name('payments.invoice');
        Route::redirect('/pdfs', '/student/study-bank')->name('pdfs.index');
        Route::get('/attendance', [StudentAttendanceController::class, 'index'])->name('attendance.index');
        Route::post('/attendance/scan', [StudentAttendanceController::class, 'scan'])->middleware('throttle:12,1')->name('attendance.scan');
        Route::post('/leave-requests', [StudentLeaveRequestController::class, 'store'])->middleware('throttle:10,1')->name('leave-requests.store');
        Route::delete('/leave-requests/{leaveRequest}', [StudentLeaveRequestController::class, 'destroy'])->name('leave-requests.destroy');
        Route::get('/help-center', [HelpCenterController::class, 'index'])->name('help-center.index');
        Route::post('/help-center/feedback', [HelpCenterController::class, 'storeFeedback'])
            ->middleware('throttle:5,1')
            ->name('help-center.feedback.store');

        Route::get('/messages', [StudentChatController::class, 'show'])->name('messages.show');
        Route::post('/messages', [StudentChatController::class, 'store'])->name('messages.store');
        Route::post('/messages/read', [StudentChatController::class, 'markRead'])->name('messages.read');
    });

    Route::middleware(['role:recruiter,employee,admin'])->prefix('recruiter')->name('recruiter.')->group(function () {
        Route::get('/projects', [RecruiterProjectController::class, 'index'])->name('projects.index');
        Route::post('/projects', [RecruiterProjectController::class, 'store'])->name('projects.store');
        Route::get('/projects/{project}', [RecruiterProjectController::class, 'show'])->name('projects.show');
        Route::put('/projects/{project}', [RecruiterProjectController::class, 'update'])->name('projects.update');
        Route::delete('/projects/{project}', [RecruiterProjectController::class, 'destroy'])->name('projects.destroy');
        Route::post('/projects/{project}/candidates/{studentProfile}', [RecruiterProjectCandidateController::class, 'store'])->name('projects.candidates.store');
        Route::delete('/projects/{project}/candidates/{studentProfile}', [RecruiterProjectCandidateController::class, 'destroy'])->name('projects.candidates.destroy');
        Route::put('/projects/{project}/requirements', [ProjectRequirementController::class, 'update'])->name('projects.requirements.update');
        Route::post('/projects/{project}/finalize', [ProjectRequirementController::class, 'finalize'])->name('projects.finalize');
        Route::post('/projects/{project}/candidates/{studentProfile}/review', [ProjectReviewController::class, 'store'])->name('projects.candidates.review');
        Route::post('/projects/{project}/candidates/{studentProfile}/approve-all', [ProjectReviewController::class, 'approveAll'])->name('projects.candidates.approve-all');
        Route::post('/projects/{project}/candidates/{studentProfile}/move', [RecruiterProjectCandidateController::class, 'move'])->name('projects.candidates.move');
    });

    Route::middleware(['role:recruiter,employee,admin,teacher,recruitment_manager'])->prefix('recruiter')->name('recruiter.')->group(function () {
        Route::get('/students', [RecruiterStudentProfileController::class, 'index'])->name('students.index');
        Route::get('/students/{studentProfile}', [RecruiterStudentProfileController::class, 'show'])->name('students.show');
        Route::post('/students/{studentProfile}/cv/translate', [RecruiterStudentProfileController::class, 'translate'])->name('students.cv.translate');
        Route::post('/students/{studentProfile}/transfer', [BatchTransferController::class, 'store'])->name('students.transfer');
    });

    Route::middleware(['role:teacher'])->prefix('teacher')->name('teacher.')->group(function () {
        Route::get('/assignments', [TeacherAssignmentController::class, 'index'])->name('assignments.index');
        Route::get('/assignments/create', [TeacherAssignmentController::class, 'create'])->name('assignments.create');
        Route::post('/assignments', [TeacherAssignmentController::class, 'store'])->name('assignments.store');
        Route::get('/assignments/{assignment}', [TeacherAssignmentController::class, 'show'])->name('assignments.show');
        Route::get('/assignments/{assignment}/duplicate', [TeacherAssignmentController::class, 'duplicateForm'])->name('assignments.duplicate');
        Route::post('/assignments/{assignment}/duplicate', [TeacherAssignmentController::class, 'duplicate'])->name('assignments.duplicate.store');
        Route::get('/assignments/{assignment}/edit', [TeacherAssignmentController::class, 'edit'])->name('assignments.edit');
        Route::put('/assignments/{assignment}', [TeacherAssignmentController::class, 'update'])->name('assignments.update');
        Route::get('/assignments/{assignment}/submissions', [TeacherAssignmentController::class, 'submissions'])->name('assignments.submissions');
        Route::get('/assignments/{assignment}/submissions/{submission}/grade', [TeacherAssignmentController::class, 'grade'])->name('assignments.submissions.grade');
        Route::put('/assignments/{assignment}/submissions/{submission}/grade', [TeacherAssignmentController::class, 'saveGrade'])->name('assignments.submissions.grade.save');
        Route::get('/assignments/{assignment}/submissions/{submission}', [TeacherAssignmentController::class, 'showSubmission'])->name('assignments.submissions.show');
        Route::get('/assignments/{assignment}/audio', [TeacherAssignmentController::class, 'audio'])->name('assignments.audio');
        Route::get('/assignments/{assignment}/questions/{question}/media', [TeacherAssignmentController::class, 'media'])->name('assignments.questions.media');

        Route::get('/materials', [TeacherMaterialController::class, 'index'])->name('materials.index');
        Route::get('/materials/folders/{folder}', [TeacherMaterialController::class, 'showFolder'])->name('materials.folders.show');
        Route::post('/materials/folders/{folder}/share', [TeacherMaterialController::class, 'shareFolder'])->name('materials.folders.share');
        Route::post('/materials/files/{file}/share', [TeacherMaterialController::class, 'shareFile'])->name('materials.files.share');
        Route::get('/materials/files/{file}/download', [TeacherMaterialController::class, 'download'])->name('materials.files.download');
        Route::get('/materials/files/{file}/playlist-items', [TeacherMaterialController::class, 'playlistItems'])->name('materials.files.playlist-items');

        Route::get('/attendance', [TeacherAttendanceController::class, 'index'])->name('attendance.index');
        Route::post('/attendance/batches/{batch}', [TeacherAttendanceController::class, 'store'])->name('attendance.store');
        Route::get('/attendance/{session}', [TeacherAttendanceController::class, 'show'])->name('attendance.show');
        Route::get('/attendance/{session}/live', [TeacherAttendanceController::class, 'live'])->name('attendance.live');
        Route::put('/attendance/{session}/students/{student}', [TeacherAttendanceController::class, 'update'])->name('attendance.students.update');
        Route::post('/attendance/{session}/close', [TeacherAttendanceController::class, 'close'])->name('attendance.close');
        Route::put('/leave-requests/{leaveRequest}', [TeacherLeaveRequestController::class, 'update'])->name('leave-requests.update');

        Route::get('/messages', [TeacherChatController::class, 'index'])->name('messages.index');
        Route::post('/messages/{conversation}', [TeacherChatController::class, 'store'])->name('messages.store');
        Route::post('/messages/{conversation}/read', [TeacherChatController::class, 'markRead'])->name('messages.read');
    });

    Route::middleware(['role:recruitment_manager'])->prefix('recruitment-manager')->name('recruitment-manager.')->group(function () {
        Route::get('/dashboard', RecruitmentManagerDashboardController::class)->name('dashboard');

        Route::get('/candidates', [RecruitmentManagerCandidateController::class, 'index'])->name('candidates.index');
        Route::put('/candidates/{studentProfile}', [RecruitmentManagerCandidateController::class, 'update'])->name('candidates.update');

        Route::get('/projects', [RecruitmentManagerProjectController::class, 'index'])->name('projects.index');
        Route::get('/projects/{project}', [RecruitmentManagerProjectController::class, 'show'])->name('projects.show');
        Route::post('/projects/{project}/requirements/{requirement}', [RecruitmentManagerProjectController::class, 'upload'])->name('projects.requirements.upload');
        Route::post('/projects/{project}/candidates/{studentProfile}/send', [RecruitmentManagerProjectController::class, 'send'])->name('projects.candidates.send');
        Route::post('/projects/{project}/message', [RecruitmentManagerProjectController::class, 'message'])->name('projects.message');

        Route::get('/inquiries', [RecruitmentManagerInquiryController::class, 'index'])->name('inquiries.index');
        Route::get('/inquiries/{inquiry}/documents/{field}', [InquiryDocumentController::class, 'download'])->name('inquiries.documents');

        Route::get('/recruiters', [RecruitmentManagerRecruiterController::class, 'index'])->name('recruiters.index');
        Route::get('/recruiters/create', [RecruitmentManagerRecruiterController::class, 'create'])->name('recruiters.create');
        Route::post('/recruiters', [RecruitmentManagerRecruiterController::class, 'store'])->name('recruiters.store');
        Route::get('/recruiters/{recruiter}/edit', [RecruitmentManagerRecruiterController::class, 'edit'])->name('recruiters.edit');
        Route::put('/recruiters/{recruiter}', [RecruitmentManagerRecruiterController::class, 'update'])->name('recruiters.update');
    });

    Route::middleware(['role:training_manager'])->prefix('training-manager')->name('training-manager.')->group(function () {
        Route::get('/dashboard', TrainingManagerDashboardController::class)->name('dashboard');

        Route::get('/feedback', [TrainingManagerCourseFeedbackController::class, 'index'])->name('feedback.index');
        Route::get('/feedback/{feedback}', [TrainingManagerCourseFeedbackController::class, 'show'])->name('feedback.show');

        Route::get('/assignments', [TrainingManagerAssignmentController::class, 'index'])->name('assignments.index');
        Route::get('/assignments/create', [TrainingManagerAssignmentController::class, 'create'])->name('assignments.create');
        Route::post('/assignments/folders', [TrainingManagerAssignmentController::class, 'storeFolder'])->name('assignments.folders.store');
        Route::put('/assignments/folders/{folder}', [TrainingManagerAssignmentController::class, 'updateFolder'])->name('assignments.folders.update');
        Route::delete('/assignments/folders/{folder}', [TrainingManagerAssignmentController::class, 'destroyFolder'])->name('assignments.folders.destroy');
        Route::post('/assignments', [TrainingManagerAssignmentController::class, 'store'])->name('assignments.store');
        Route::get('/assignments/{assignment}', [TrainingManagerAssignmentController::class, 'show'])->name('assignments.show');
        Route::patch('/assignments/{assignment}/folder', [TrainingManagerAssignmentController::class, 'moveToFolder'])->name('assignments.folder.move');
        Route::get('/assignments/{assignment}/duplicate', [TrainingManagerAssignmentController::class, 'duplicateForm'])->name('assignments.duplicate');
        Route::post('/assignments/{assignment}/duplicate', [TrainingManagerAssignmentController::class, 'duplicate'])->name('assignments.duplicate.store');
        Route::get('/assignments/{assignment}/edit', [TrainingManagerAssignmentController::class, 'edit'])->name('assignments.edit');
        Route::put('/assignments/{assignment}', [TrainingManagerAssignmentController::class, 'update'])->name('assignments.update');
        Route::get('/assignments/{assignment}/audio', [TrainingManagerAssignmentController::class, 'audio'])->name('assignments.audio');
        Route::get('/assignments/{assignment}/questions/{question}/media', [TrainingManagerAssignmentController::class, 'media'])->name('assignments.questions.media');
        Route::get('/assignments/{assignment}/submissions', [TrainingManagerAssignmentController::class, 'submissions'])->name('assignments.submissions');
        Route::get('/assignments/{assignment}/submissions/{submission}/grade', [TrainingManagerAssignmentController::class, 'grade'])->name('assignments.submissions.grade');
        Route::put('/assignments/{assignment}/submissions/{submission}/grade', [TrainingManagerAssignmentController::class, 'saveGrade'])->name('assignments.submissions.grade.save');

        Route::get('/trainers', [TrainingManagerTrainerController::class, 'index'])->name('trainers.index');
        Route::get('/trainers/create', [TrainingManagerTrainerController::class, 'create'])->name('trainers.create');
        Route::post('/trainers', [TrainingManagerTrainerController::class, 'store'])->name('trainers.store');
        Route::get('/trainers/{trainer}', [TrainingManagerTrainerController::class, 'show'])->name('trainers.show');
        Route::get('/trainers/{trainer}/edit', [TrainingManagerTrainerController::class, 'edit'])->name('trainers.edit');
        Route::put('/trainers/{trainer}', [TrainingManagerTrainerController::class, 'update'])->name('trainers.update');
        Route::delete('/trainers/{trainer}', [TrainingManagerTrainerController::class, 'destroy'])->name('trainers.destroy');

        Route::get('/batches', [TrainingManagerBatchController::class, 'index'])->name('batches.index');
        Route::get('/batches/create', [TrainingManagerBatchController::class, 'create'])->name('batches.create');
        Route::post('/batches', [TrainingManagerBatchController::class, 'store'])->name('batches.store');
        Route::get('/batches/enrollment-template', [TrainingManagerBatchEnrollmentController::class, 'downloadTemplate'])->name('batches.enrollment-template');
        Route::get('/batches/unenrolled-candidates', [TrainingManagerBatchEnrollmentController::class, 'searchCandidates'])->name('batches.unenrolled-candidates');
        Route::get('/batches/{batch}', [TrainingManagerBatchController::class, 'show'])->name('batches.show');
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
        Route::post('/library/folders/{folder}/youtube-playlist', [TrainingManagerLibraryController::class, 'storeYoutubePlaylist'])->name('library.youtube-playlist.store');
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
        Route::get('/library/files/{file}/playlist-items', [TrainingManagerLibraryController::class, 'playlistItems'])->name('library.files.playlist-items');
    });
});

require __DIR__.'/settings.php';
