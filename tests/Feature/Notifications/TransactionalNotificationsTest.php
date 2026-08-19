<?php

namespace Tests\Feature\Notifications;

use App\Enums\PaymentStatus;
use App\Enums\RegistrationFeePaidVia;
use App\Enums\UserRole;
use App\Models\ActivationCode;
use App\Models\Batch;
use App\Models\BatchEnrollment;
use App\Models\EducationLevel;
use App\Models\MaterialFolder;
use App\Models\Payment;
use App\Models\User;
use App\Notifications\ActivationCodeIssued;
use App\Notifications\EnrolledInBatch;
use App\Notifications\InquirySubmitted;
use App\Notifications\MaterialAssignedToTrainer;
use App\Notifications\MaterialSharedWithBatch;
use App\Notifications\RegistrationActivated;
use App\Notifications\StudentRegistered;
use App\Notifications\WelcomeWithCredentials;
use App\Services\BatchEnrollmentService;
use App\Services\BulkActivationCodeService;
use App\Services\CandidateImportService;
use App\Services\MaterialAssignmentService;
use App\Services\MaterialLibraryService;
use App\Services\PaymentVerificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class TransactionalNotificationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_trainer_sends_welcome_with_credentials(): void
    {
        Notification::fake();

        $manager = User::factory()->trainingManager()->create();

        $this->actingAs($manager)
            ->post(route('training-manager.trainers.store'), [
                'name' => 'New Trainer',
                'email' => 'trainer-notify@example.com',
                'password' => 'password',
            ])
            ->assertRedirect();

        $trainer = User::query()->where('email', 'trainer-notify@example.com')->firstOrFail();

        Notification::assertSentTo(
            $trainer,
            WelcomeWithCredentials::class,
            function (WelcomeWithCredentials $notification): bool {
                return $notification->plainPassword === 'password'
                    && $notification->role === UserRole::Teacher;
            },
        );
    }

    public function test_student_registration_sends_welcome_notification(): void
    {
        Notification::fake();

        $this->post(route('student.register.store'), [
            'first_name' => 'Nanuka',
            'last_name' => 'Thapa',
            'email' => 'register-notify@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'state' => 'Sikkim',
            'special_id' => 'COI',
        ])->assertRedirect(route('dashboard'));

        $user = User::query()->where('email', 'register-notify@example.com')->firstOrFail();

        Notification::assertSentTo($user, StudentRegistered::class);
    }

    public function test_candidate_import_sends_welcome_with_temp_password(): void
    {
        Notification::fake();

        $path = $this->writeImportSpreadsheet([
            ['Thapa', 'Nanuka', 'import-notify@example.com', 'Sikkim', 'COI', '2024'],
        ]);

        app(CandidateImportService::class)->import($path);

        $user = User::query()->where('email', 'import-notify@example.com')->firstOrFail();

        Notification::assertSentTo(
            $user,
            WelcomeWithCredentials::class,
            function (WelcomeWithCredentials $notification) use ($user): bool {
                return $notification->role === UserRole::Student
                    && $notification->plainPassword !== ''
                    && $notification->plainPassword !== $user->password;
            },
        );
    }

    public function test_bulk_activation_code_emails_student(): void
    {
        Notification::fake();

        $student = User::factory()->student()->unpaid()->create();

        $codes = app(BulkActivationCodeService::class)->generateForUnpaidStudents([$student->id]);

        $this->assertNotEmpty($codes);

        Notification::assertSentTo(
            $student,
            ActivationCodeIssued::class,
            fn (ActivationCodeIssued $notification): bool => $notification->code === $codes[0]['registration_code'],
        );
    }

    public function test_redeeming_activation_code_sends_registration_activated(): void
    {
        Notification::fake();

        $user = User::factory()->student()->unpaid()->create([
            'student_number' => 'SK-ACT-0001',
        ]);

        $code = ActivationCode::query()->create([
            'code' => 'BV-26-TEST',
            'email' => $user->email,
            'assigned_user_id' => $user->id,
            'student_number' => $user->student_number,
        ]);

        $this->actingAs($user)->post(route('activation-codes.redeem'), [
            'code' => $code->code,
        ])->assertRedirect(route('dashboard'));

        Notification::assertSentTo(
            $user,
            RegistrationActivated::class,
            fn (RegistrationActivated $notification): bool => $notification->via === RegistrationFeePaidVia::ActivationCode
                && $notification->isRenewal === false
                && filled($notification->expiresAt),
        );
    }

    public function test_payment_activation_sends_registration_activated(): void
    {
        Notification::fake();

        $user = User::factory()->student()->unpaid()->create();

        $payment = Payment::query()->create([
            'user_id' => $user->id,
            'gateway_order_id' => 'order_test_1',
            'amount' => 10000,
            'currency' => 'INR',
            'status' => PaymentStatus::Pending,
        ]);

        app(PaymentVerificationService::class)->activateStudentFromPayment($payment, 'pay_test_1');

        Notification::assertSentTo(
            $user->fresh(),
            RegistrationActivated::class,
            fn (RegistrationActivated $notification): bool => $notification->via === RegistrationFeePaidVia::Cashfree
                && $notification->isRenewal === false
                && filled($notification->expiresAt),
        );
    }

    public function test_renewal_payment_sends_renewal_activated_notification(): void
    {
        Notification::fake();

        $user = User::factory()->student()->paid()->create();

        $payment = Payment::query()->create([
            'user_id' => $user->id,
            'gateway_order_id' => 'order_renew_1',
            'amount' => 10000,
            'currency' => 'INR',
            'status' => PaymentStatus::Pending,
            'duration_months' => 12,
        ]);

        app(PaymentVerificationService::class)->activateStudentFromPayment($payment, 'pay_renew_1');

        Notification::assertSentTo(
            $user->fresh(),
            RegistrationActivated::class,
            fn (RegistrationActivated $notification): bool => $notification->isRenewal === true
                && filled($notification->expiresAt),
        );
    }

    public function test_first_payment_and_renewal_emails_use_different_copy(): void
    {
        $user = User::factory()->student()->unpaid()->create(['name' => 'Resend User']);
        $expiresAt = now()->addYear()->toIso8601String();

        $firstMail = (new RegistrationActivated(
            RegistrationFeePaidVia::Cashfree,
            null,
            false,
            $expiresAt,
        ))->toMail($user);

        $renewalMail = (new RegistrationActivated(
            RegistrationFeePaidVia::Cashfree,
            null,
            true,
            $expiresAt,
        ))->toMail($user);

        $this->assertSame('Your Bilingual Vitae dashboard is unlocked', $firstMail->subject);
        $this->assertTrue(collect($firstMail->introLines)->contains(
            fn (string $line): bool => str_contains($line, 'registration payment was received'),
        ));
        $this->assertTrue(collect($firstMail->introLines)->contains(
            fn (string $line): bool => str_contains($line, 'Your account will be active till'),
        ));

        $this->assertSame('Your Bilingual Vitae access has been extended', $renewalMail->subject);
        $this->assertTrue(collect($renewalMail->introLines)->contains(
            fn (string $line): bool => str_contains($line, 'renewal payment was received'),
        ));
        $this->assertTrue(collect($renewalMail->introLines)->contains(
            fn (string $line): bool => str_contains($line, 'your account will now be active till'),
        ));
    }

    public function test_enrolling_student_sends_batch_notification(): void
    {
        Notification::fake();

        $batch = Batch::factory()->create(['name' => 'German A1']);
        $student = User::factory()->paid()->create(['role' => UserRole::Student]);

        app(BatchEnrollmentService::class)->enroll($batch, $student);

        Notification::assertSentTo(
            $student,
            EnrolledInBatch::class,
            fn (EnrolledInBatch $notification): bool => $notification->batch->is($batch),
        );

        // Idempotent re-enroll should not notify again
        Notification::fake();
        app(BatchEnrollmentService::class)->enroll($batch, $student);
        Notification::assertNothingSent();
    }

    public function test_sharing_material_with_batch_notifies_enrolled_students_only_for_new_batches(): void
    {
        Notification::fake();
        Storage::fake('local');

        $manager = User::factory()->trainingManager()->create();
        $batch = Batch::factory()->create(['name' => 'Share Batch', 'is_active' => true]);
        $student = User::factory()->paid()->create(['role' => UserRole::Student]);

        BatchEnrollment::query()->create([
            'batch_id' => $batch->id,
            'student_id' => $student->id,
        ]);

        $folder = app(MaterialLibraryService::class)->createFolder($manager, 'Grammar Pack');
        $assignments = app(MaterialAssignmentService::class);

        $assignments->shareFolderWithBatches($manager, $folder, [$batch->id]);

        Notification::assertSentTo(
            $student,
            MaterialSharedWithBatch::class,
            fn (MaterialSharedWithBatch $notification): bool => $notification->materialTitle === 'Grammar Pack'
                && $notification->batchName === 'Share Batch',
        );

        Notification::fake();
        $assignments->shareFolderWithBatches($manager, $folder, [$batch->id]);
        Notification::assertNothingSent();
    }

    public function test_assigning_material_to_trainer_notifies_newly_assigned_trainers(): void
    {
        Notification::fake();

        $manager = User::factory()->trainingManager()->create();
        $trainer = User::factory()->teacher()->create();
        $folder = MaterialFolder::query()->create([
            'name' => 'Trainer Pack',
            'created_by' => $manager->id,
        ]);

        app(MaterialAssignmentService::class)->assignFolder($manager, $folder, [$trainer->id]);

        Notification::assertSentTo(
            $trainer,
            MaterialAssignedToTrainer::class,
            fn (MaterialAssignedToTrainer $notification): bool => $notification->materialTitle === 'Trainer Pack',
        );
    }

    public function test_inquiry_submission_notifies_ops_inbox(): void
    {
        Notification::fake();

        EducationLevel::query()->create([
            'name' => 'Graduate',
            'slug' => 'graduate',
            'sort_order' => 1,
            'is_visible' => true,
        ]);

        $this->post('/inquiries', [
            'type' => 'candidate',
            'first_name' => 'Nanuka',
            'last_name' => 'Thapa',
            'date_of_birth' => '1990-01-01',
            'email' => 'inquiry-notify@example.com',
            'phone' => '+91 98325 81198',
            'city' => 'Gangtok',
            'country' => 'India',
            'education_level' => 'graduate',
            'years_of_experience' => '2',
            'languages' => 'English',
            'qualification_certificate' => UploadedFile::fake()->create('cert.pdf', 100, 'application/pdf'),
            'resume' => UploadedFile::fake()->create('resume.pdf', 100, 'application/pdf'),
        ])->assertRedirect();

        Notification::assertSentOnDemand(
            InquirySubmitted::class,
            function (InquirySubmitted $notification, array $channels, object $notifiable): bool {
                return in_array('mail', $channels, true)
                    && ($notifiable->routes['mail'] ?? null) === config('mail.ops_address');
            },
        );
    }

    public function test_student_can_view_and_mark_notifications_as_read(): void
    {
        $student = User::factory()->paid()->create(['role' => UserRole::Student]);

        $student->notifyNow(new StudentRegistered);

        $this->actingAs($student)
            ->get(route('student.notifications.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('app/student/notifications')
                ->has('notifications', 1)
                ->where('unread_count', 1)
            );

        $notificationId = $student->notifications()->firstOrFail()->id;

        $this->actingAs($student)
            ->post(route('student.notifications.read', $notificationId))
            ->assertRedirect();

        $this->assertNotNull($student->notifications()->firstOrFail()->read_at);
    }

    /**
     * @param  list<list<string>>  $rows
     */
    private function writeImportSpreadsheet(array $rows): string
    {
        $headers = CandidateImportService::REQUIRED_HEADERS;
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();

        foreach ($headers as $index => $header) {
            $sheet->setCellValue([$index + 1, 1], $header);
        }

        foreach ($rows as $rowIndex => $row) {
            foreach ($row as $colIndex => $value) {
                $sheet->setCellValueExplicit(
                    [$colIndex + 1, $rowIndex + 2],
                    $value,
                    DataType::TYPE_STRING,
                );
            }
        }

        $path = storage_path('framework/testing/notify-import-'.uniqid('', true).'.xlsx');
        (new Xlsx($spreadsheet))->save($path);

        return $path;
    }
}
