<?php

namespace Tests\Feature\Student;

use App\Enums\UserRole;
use App\Models\Batch;
use App\Models\BatchEnrollment;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class StudentCvTest extends TestCase
{
    use RefreshDatabase;

    public function test_unpaid_student_cannot_open_cv(): void
    {
        $user = User::factory()->unpaid()->create([
            'role' => UserRole::Student,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('student.cv.edit'))
            ->assertForbidden();
    }

    public function test_paid_student_can_view_and_update_cv(): void
    {
        Storage::fake('public');

        $user = User::factory()->paid()->create([
            'role' => UserRole::Student,
            'first_name' => 'Dominique',
            'last_name' => 'Sangbo',
            'email_verified_at' => now(),
            'state_code' => 'SK',
            'special_id' => 'COI',
            'registration_year' => 2026,
            'student_number' => 'SK-COI-2026-01',
        ]);

        StudentProfile::query()->create([
            'user_id' => $user->id,
            'mobile_whatsapp' => '+911234567890',
        ]);

        $this->actingAs($user)
            ->get(route('student.cv.edit'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('app/student/cv')
                ->has('cv')
                ->has('options.civilStatuses')
                ->where('options.civilStatuses', ['Unmarried', 'Married'])
                ->where('cv.candidate_id', 'SK-COI-2026-01'));

        $photo = UploadedFile::fake()->image('passport.jpg', 400, 400);

        $response = $this->actingAs($user)->post(route('student.cv.update'), [
            'first_name' => 'Dominique',
            'last_name' => 'Sangbo',
            'mobile_whatsapp' => '+919999999999',
            'date_of_birth' => '1990-01-15',
            'place_of_birth' => 'Gangtok, Sikkim',
            'civil_status' => 'Unmarried',
            'gender' => 'Female',
            'nationality' => 'Indian',
            'introduction' => 'Dedicated nurse with bilingual skills.',
            'skills' => ['Patient education', 'Leadership'],
            'hobbies' => 'Reading',
            'passport_photo' => $photo,
            'vitae' => [
                'is_newbie' => false,
                'experiences' => [
                    [
                        'start_date' => '03/2022',
                        'end_date' => '06/2024',
                        'designation' => 'Senior Nurse',
                        'department' => 'ICU',
                        'organization' => 'NEIGRIHMS',
                        'city' => 'Shillong',
                        'state' => 'Meghalaya',
                        'cases_handled' => 'Critical care cases',
                        'medical_equipment' => 'Ventilator, Infusion pump',
                        'bed_capacity' => '500',
                    ],
                ],
                'qualifications' => [
                    [
                        'start_date' => '06/2018',
                        'end_date' => '05/2022',
                        'title' => 'M.Sc Nursing',
                        'college' => 'Assam down Town University',
                        'university' => 'Assam down Town University',
                        'city' => 'Guwahati',
                        'state' => 'Assam',
                        'country' => 'India',
                        'language_of_instruction' => 'English',
                    ],
                ],
                'trainings' => [],
                'certifications' => [
                    [
                        'name' => 'BLS Provider',
                        'description' => 'Basic Life Support certification',
                    ],
                ],
                'licenses' => [
                    [
                        'title' => 'Registered Nurse with Karnataka Nursing Council',
                        'registration_number' => 'RN-123',
                    ],
                ],
                'bilingual' => [
                    [
                        'language' => 'Nepali',
                        'proficiency' => 'Native',
                    ],
                    [
                        'language' => 'German',
                        'proficiency' => 'Professional',
                    ],
                    [
                        'language' => 'Hindi',
                        'proficiency' => 'Intermediate',
                    ],
                ],
            ],
        ]);

        $response->assertRedirect(route('student.cv.edit'));

        $user->refresh();
        $profile = $user->studentProfile()->first();

        $this->assertSame('+919999999999', $profile->mobile_whatsapp);
        $this->assertSame('Dedicated nurse with bilingual skills.', $profile->introduction);
        $this->assertSame(['Patient education', 'Leadership'], $profile->skills);
        $this->assertNotNull($profile->passport_photo_path);
        $this->assertSame('NEIGRIHMS', $profile->vitae_data['experiences'][0]['organization']);
        $this->assertSame('03/2022', $profile->vitae_data['experiences'][0]['start_date']);
        $this->assertSame('06/2024', $profile->vitae_data['experiences'][0]['end_date']);
        $this->assertSame('Ventilator, Infusion pump', $profile->vitae_data['experiences'][0]['medical_equipment']);
        $this->assertSame('500', $profile->vitae_data['experiences'][0]['bed_capacity']);
        $this->assertSame('M.Sc Nursing', $profile->vitae_data['qualifications'][0]['title']);
        $this->assertSame('India', $profile->vitae_data['qualifications'][0]['country']);
        $this->assertSame('06/2018', $profile->vitae_data['qualifications'][0]['start_date']);
        $this->assertSame('Assam down Town University', $profile->vitae_data['qualifications'][0]['college']);
        $this->assertSame('Assam down Town University', $profile->vitae_data['qualifications'][0]['university']);
        $this->assertSame('BLS Provider', $profile->vitae_data['certifications'][0]['name']);
        $this->assertSame('Basic Life Support certification', $profile->vitae_data['certifications'][0]['description']);
        $this->assertSame('Registered Nurse with Karnataka Nursing Council', $profile->vitae_data['licenses'][0]['title']);
        $this->assertSame('RN-123', $profile->vitae_data['licenses'][0]['registration_number']);
        $this->assertSame('German', $profile->vitae_data['bilingual'][1]['language']);
        $this->assertSame('Professional', $profile->vitae_data['bilingual'][1]['proficiency']);
        $this->assertSame('Hindi', $profile->vitae_data['bilingual'][2]['language']);
        $this->assertSame('Intermediate', $profile->vitae_data['bilingual'][2]['proficiency']);
        $this->assertCount(3, $profile->vitae_data['bilingual']);
        Storage::disk('public')->assertExists($profile->passport_photo_path);
    }

    public function test_cv_saves_certifications_and_simplified_licenses_with_files(): void
    {
        Storage::fake('public');

        $user = User::factory()->paid()->create([
            'role' => UserRole::Student,
            'first_name' => 'Dominique',
            'last_name' => 'Sangbo',
            'email_verified_at' => now(),
        ]);

        StudentProfile::query()->create([
            'user_id' => $user->id,
        ]);

        $certFile = UploadedFile::fake()->create('bls-cert.pdf', 100, 'application/pdf');
        $licenseFile = UploadedFile::fake()->create('rn-license.pdf', 100, 'application/pdf');

        $this->actingAs($user)
            ->post(route('student.cv.update'), [
                'first_name' => 'Dominique',
                'last_name' => 'Sangbo',
                'vitae' => [
                    'is_newbie' => true,
                    'experiences' => [],
                    'qualifications' => [],
                    'trainings' => [],
                    'certifications' => [
                        [
                            'name' => 'ACLS',
                            'description' => 'Advanced cardiac life support',
                            'certificate' => $certFile,
                        ],
                    ],
                    'licenses' => [
                        [
                            'title' => 'Staff Nurse',
                            'registration_number' => 'LIC-99',
                            'certificate' => $licenseFile,
                        ],
                    ],
                    'bilingual' => [],
                ],
            ])
            ->assertRedirect(route('student.cv.edit'));

        $user->refresh();
        $profile = $user->studentProfile()->first();

        $this->assertSame('ACLS', $profile->vitae_data['certifications'][0]['name']);
        $this->assertSame('Advanced cardiac life support', $profile->vitae_data['certifications'][0]['description']);
        $this->assertNotNull($profile->vitae_data['certifications'][0]['certificate_path']);
        $this->assertSame('Staff Nurse', $profile->vitae_data['licenses'][0]['title']);
        $this->assertSame('LIC-99', $profile->vitae_data['licenses'][0]['registration_number']);
        $this->assertNotNull($profile->vitae_data['licenses'][0]['certificate_path']);
        Storage::disk('public')->assertExists($profile->vitae_data['certifications'][0]['certificate_path']);
        Storage::disk('public')->assertExists($profile->vitae_data['licenses'][0]['certificate_path']);
    }

    public function test_cv_saves_seminar_file_and_publication_link(): void
    {
        Storage::fake('public');

        $user = User::factory()->paid()->create([
            'role' => UserRole::Student,
            'first_name' => 'Dominique',
            'last_name' => 'Sangbo',
            'email_verified_at' => now(),
        ]);

        StudentProfile::query()->create([
            'user_id' => $user->id,
        ]);

        $seminarFile = UploadedFile::fake()->create('seminar-cert.pdf', 100, 'application/pdf');

        $this->actingAs($user)
            ->post(route('student.cv.update'), [
                'first_name' => 'Dominique',
                'last_name' => 'Sangbo',
                'vitae' => [
                    'is_newbie' => true,
                    'experiences' => [],
                    'qualifications' => [],
                    'trainings' => [],
                    'seminars' => [
                        [
                            'date' => '2024-03-15',
                            'topic' => 'Infection control workshop',
                            'organization' => 'WHO India',
                            'location' => 'Delhi',
                            'evidence_type' => 'file',
                            'certificate' => $seminarFile,
                        ],
                    ],
                    'presentations' => [],
                    'publications' => [
                        [
                            'date' => '01/2025',
                            'topic' => 'Nursing education outcomes',
                            'organization' => 'IJN',
                            'location' => 'Online',
                            'evidence_type' => 'link',
                            'link_url' => 'https://example.com/nursing-education',
                        ],
                    ],
                    'certifications' => [],
                    'licenses' => [],
                    'bilingual' => [],
                ],
            ])
            ->assertRedirect(route('student.cv.edit'));

        $user->refresh();
        $profile = $user->studentProfile()->first();

        $this->assertSame('Infection control workshop', $profile->vitae_data['seminars'][0]['topic']);
        $this->assertSame('WHO India', $profile->vitae_data['seminars'][0]['organization']);
        $this->assertSame('Delhi', $profile->vitae_data['seminars'][0]['location']);
        $this->assertSame('file', $profile->vitae_data['seminars'][0]['evidence_type']);
        $this->assertNotNull($profile->vitae_data['seminars'][0]['certificate_path']);
        $this->assertSame('Nursing education outcomes', $profile->vitae_data['publications'][0]['topic']);
        $this->assertSame('link', $profile->vitae_data['publications'][0]['evidence_type']);
        $this->assertSame('https://example.com/nursing-education', $profile->vitae_data['publications'][0]['link_url']);
        $this->assertNull($profile->vitae_data['publications'][0]['certificate_path']);
        Storage::disk('public')->assertExists($profile->vitae_data['seminars'][0]['certificate_path']);
    }

    public function test_cv_saves_fresher_current_plans(): void
    {
        $user = User::factory()->paid()->create([
            'role' => UserRole::Student,
            'first_name' => 'Dominique',
            'last_name' => 'Sangbo',
            'email_verified_at' => now(),
        ]);

        StudentProfile::query()->create([
            'user_id' => $user->id,
        ]);

        $this->actingAs($user)
            ->post(route('student.cv.update'), [
                'first_name' => 'Dominique',
                'last_name' => 'Sangbo',
                'gap_reasons' => 'Preparing for German B2 and clinical training abroad.',
                'vitae' => [
                    'is_newbie' => true,
                    'experiences' => [],
                    'qualifications' => [],
                    'trainings' => [],
                    'certifications' => [],
                    'licenses' => [],
                    'bilingual' => [],
                ],
            ])
            ->assertRedirect(route('student.cv.edit'));

        $user->refresh();
        $profile = $user->studentProfile()->first();

        $this->assertTrue($profile->vitae_data['is_newbie']);
        $this->assertSame(
            'Preparing for German B2 and clinical training abroad.',
            $profile->gap_reasons,
        );
        $this->assertSame([], $profile->vitae_data['experiences']);
    }

    public function test_cv_saves_experience_with_dates_and_certificate(): void
    {
        Storage::fake('public');

        $user = User::factory()->paid()->create([
            'role' => UserRole::Student,
            'first_name' => 'Dominique',
            'last_name' => 'Sangbo',
            'email_verified_at' => now(),
        ]);

        StudentProfile::query()->create([
            'user_id' => $user->id,
        ]);

        $certificate = UploadedFile::fake()->create('experience-letter.pdf', 100, 'application/pdf');

        $this->actingAs($user)
            ->post(route('student.cv.update'), [
                'first_name' => 'Dominique',
                'last_name' => 'Sangbo',
                'vitae' => [
                    'is_newbie' => false,
                    'experiences' => [
                        [
                            'start_date' => '01/2023',
                            'end_date' => '12/2024',
                            'designation' => 'Staff Nurse',
                            'department' => 'Emergency',
                            'organization' => 'Civil Hospital',
                            'city' => 'Gangtok',
                            'state' => 'Sikkim',
                            'cases_handled' => 'Trauma and emergency cases',
                            'medical_equipment' => 'Defibrillator',
                            'bed_capacity' => '200',
                            'certificate' => $certificate,
                        ],
                    ],
                    'qualifications' => [],
                    'trainings' => [],
                    'certifications' => [],
                    'licenses' => [],
                    'bilingual' => [],
                ],
            ])
            ->assertRedirect(route('student.cv.edit'));

        $user->refresh();
        $profile = $user->studentProfile()->first();
        $experience = $profile->vitae_data['experiences'][0];

        $this->assertFalse($profile->vitae_data['is_newbie']);
        $this->assertSame('01/2023', $experience['start_date']);
        $this->assertSame('12/2024', $experience['end_date']);
        $this->assertSame('Staff Nurse', $experience['designation']);
        $this->assertSame('Defibrillator', $experience['medical_equipment']);
        $this->assertSame('200', $experience['bed_capacity']);
        $this->assertNotNull($experience['certificate_path']);
        Storage::disk('public')->assertExists($experience['certificate_path']);
    }

    public function test_cv_saves_bilingual_language_proficiency_and_certificate(): void
    {
        Storage::fake('public');

        $user = User::factory()->paid()->create([
            'role' => UserRole::Student,
            'first_name' => 'Dominique',
            'last_name' => 'Sangbo',
            'email_verified_at' => now(),
        ]);

        StudentProfile::query()->create([
            'user_id' => $user->id,
        ]);

        $certificate = UploadedFile::fake()->create('goethe-b2.pdf', 100, 'application/pdf');

        $this->actingAs($user)
            ->post(route('student.cv.update'), [
                'first_name' => 'Dominique',
                'last_name' => 'Sangbo',
                'vitae' => [
                    'is_newbie' => true,
                    'experiences' => [],
                    'qualifications' => [],
                    'trainings' => [],
                    'certifications' => [],
                    'licenses' => [],
                    'bilingual' => [
                        [
                            'language' => 'English',
                            'language_type' => 'Regional/Foreign',
                            'proficiency' => 'Native',
                            'certification_level' => 'C1',
                        ],
                        [
                            'language' => 'German',
                            'language_type' => 'Foreign Language',
                            'proficiency' => 'Professional',
                            'certification_level' => 'B2',
                            'certificate' => $certificate,
                        ],
                    ],
                ],
            ])
            ->assertRedirect(route('student.cv.edit'));

        $user->refresh();
        $profile = $user->studentProfile()->first();

        $this->assertSame('English', $profile->vitae_data['bilingual'][0]['language']);
        $this->assertSame('Regional/Foreign', $profile->vitae_data['bilingual'][0]['language_type']);
        $this->assertSame('Native', $profile->vitae_data['bilingual'][0]['proficiency']);
        $this->assertSame('C1', $profile->vitae_data['bilingual'][0]['certification_level']);
        $this->assertSame('German', $profile->vitae_data['bilingual'][1]['language']);
        $this->assertSame('Foreign Language', $profile->vitae_data['bilingual'][1]['language_type']);
        $this->assertSame('Professional', $profile->vitae_data['bilingual'][1]['proficiency']);
        $this->assertSame('B2', $profile->vitae_data['bilingual'][1]['certification_level']);
        $this->assertNotNull($profile->vitae_data['bilingual'][1]['certificate_path']);
        Storage::disk('public')->assertExists($profile->vitae_data['bilingual'][1]['certificate_path']);
        $this->assertSame(
            ['English — Native', 'German — Professional'],
            $profile->languages,
        );
    }

    public function test_cv_rejects_invalid_bilingual_language_type_and_certification_level(): void
    {
        $user = User::factory()->paid()->create([
            'role' => UserRole::Student,
            'first_name' => 'Dominique',
            'last_name' => 'Sangbo',
            'email_verified_at' => now(),
        ]);

        StudentProfile::query()->create([
            'user_id' => $user->id,
        ]);

        $this->actingAs($user)
            ->from(route('student.cv.edit'))
            ->post(route('student.cv.update'), [
                'first_name' => 'Dominique',
                'last_name' => 'Sangbo',
                'vitae' => [
                    'is_newbie' => true,
                    'experiences' => [],
                    'qualifications' => [],
                    'trainings' => [],
                    'certifications' => [],
                    'licenses' => [],
                    'bilingual' => [
                        [
                            'language' => 'Nepali',
                            'language_type' => 'Dialect',
                            'proficiency' => 'Native',
                            'certification_level' => 'Z9',
                        ],
                    ],
                ],
            ])
            ->assertRedirect(route('student.cv.edit'))
            ->assertSessionHasErrors([
                'vitae.bilingual.0.language_type',
                'vitae.bilingual.0.certification_level',
            ]);
    }

    public function test_cv_clears_certification_level_for_regional_languages(): void
    {
        $user = User::factory()->paid()->create([
            'role' => UserRole::Student,
            'first_name' => 'Dominique',
            'last_name' => 'Sangbo',
            'email_verified_at' => now(),
        ]);

        StudentProfile::query()->create([
            'user_id' => $user->id,
        ]);

        $this->actingAs($user)
            ->post(route('student.cv.update'), [
                'first_name' => 'Dominique',
                'last_name' => 'Sangbo',
                'vitae' => [
                    'is_newbie' => true,
                    'experiences' => [],
                    'qualifications' => [],
                    'trainings' => [],
                    'certifications' => [],
                    'licenses' => [],
                    'bilingual' => [
                        [
                            'language' => 'Kannada',
                            'language_type' => 'Regional',
                            'proficiency' => 'Intermediate',
                            'certification_level' => 'A1',
                        ],
                        [
                            'language' => 'German',
                            'language_type' => 'Foreign Language',
                            'proficiency' => 'Professional',
                            'certification_level' => 'B2',
                        ],
                    ],
                ],
            ])
            ->assertRedirect(route('student.cv.edit'));

        $user->refresh();
        $profile = $user->studentProfile()->first();

        $this->assertSame('Kannada', $profile->vitae_data['bilingual'][0]['language']);
        $this->assertSame('Regional', $profile->vitae_data['bilingual'][0]['language_type']);
        $this->assertNull($profile->vitae_data['bilingual'][0]['certification_level']);
        $this->assertSame('German', $profile->vitae_data['bilingual'][1]['language']);
        $this->assertSame('B2', $profile->vitae_data['bilingual'][1]['certification_level']);
    }

    public function test_cv_saves_passport_and_license_documents(): void
    {
        Storage::fake('public');

        $user = User::factory()->paid()->create([
            'role' => UserRole::Student,
            'first_name' => 'Dominique',
            'last_name' => 'Sangbo',
            'email_verified_at' => now(),
        ]);

        StudentProfile::query()->create([
            'user_id' => $user->id,
        ]);

        $this->actingAs($user)
            ->post(route('student.cv.update'), [
                'first_name' => 'Dominique',
                'last_name' => 'Sangbo',
                'passport_or_id_number' => 'P1234567',
                'drivers_license' => 'DL-2W-999',
                'passport_document' => UploadedFile::fake()->create('passport.pdf', 200, 'application/pdf'),
                'drivers_license_document' => UploadedFile::fake()->image('license.jpg', 600, 400),
            ])
            ->assertRedirect(route('student.cv.edit'));

        $profile = $user->studentProfile()->first();

        $this->assertSame('P1234567', $profile->passport_or_id_number);
        $this->assertSame('DL-2W-999', $profile->drivers_license);
        $this->assertNotNull($profile->passport_document_path);
        $this->assertNotNull($profile->drivers_license_document_path);
        Storage::disk('public')->assertExists($profile->passport_document_path);
        Storage::disk('public')->assertExists($profile->drivers_license_document_path);
    }

    public function test_cv_rejects_non_image_photo_upload(): void
    {
        Storage::fake('public');

        $user = User::factory()->paid()->create([
            'role' => UserRole::Student,
            'first_name' => 'Dominique',
            'last_name' => 'Sangbo',
            'email_verified_at' => now(),
        ]);

        StudentProfile::query()->create([
            'user_id' => $user->id,
        ]);

        $this->actingAs($user)
            ->post(route('student.cv.update'), [
                'first_name' => 'Dominique',
                'last_name' => 'Sangbo',
                'passport_photo' => UploadedFile::fake()->create('notes.pdf', 100, 'application/pdf'),
            ])
            ->assertSessionHasErrors('passport_photo');
    }

    public function test_cv_rejects_invalid_phone_and_angle_brackets_in_short_fields(): void
    {
        $user = User::factory()->paid()->create([
            'role' => UserRole::Student,
            'first_name' => 'Dominique',
            'last_name' => 'Sangbo',
            'email_verified_at' => now(),
        ]);

        StudentProfile::query()->create([
            'user_id' => $user->id,
        ]);

        $this->actingAs($user)
            ->from(route('student.cv.edit'))
            ->post(route('student.cv.update'), [
                'first_name' => 'Dominique',
                'last_name' => 'Sangbo',
                'mobile_whatsapp' => 'not-a-phone',
                'nationality' => 'Indian < Test',
            ])
            ->assertRedirect(route('student.cv.edit'))
            ->assertSessionHasErrors(['mobile_whatsapp', 'nationality']);
    }

    public function test_cv_rejects_phone_without_country_code_and_normalizes_valid_phone(): void
    {
        $user = User::factory()->paid()->create([
            'role' => UserRole::Student,
            'first_name' => 'Dominique',
            'last_name' => 'Sangbo',
            'email_verified_at' => now(),
        ]);

        $profile = StudentProfile::query()->create([
            'user_id' => $user->id,
        ]);

        $this->actingAs($user)
            ->from(route('student.cv.edit'))
            ->post(route('student.cv.update'), [
                'first_name' => 'Dominique',
                'last_name' => 'Sangbo',
                'mobile_whatsapp' => '9832581198',
            ])
            ->assertRedirect(route('student.cv.edit'))
            ->assertSessionHasErrors(['mobile_whatsapp']);

        $this->actingAs($user)
            ->from(route('student.cv.edit'))
            ->post(route('student.cv.update'), [
                'first_name' => 'Dominique',
                'last_name' => 'Sangbo',
                'mobile_whatsapp' => '+91 98325 81198',
            ])
            ->assertRedirect(route('student.cv.edit'))
            ->assertSessionDoesntHaveErrors();

        $profile->refresh();
        $this->assertSame('+919832581198', $profile->mobile_whatsapp);
    }

    public function test_cv_strips_html_tags_from_long_text_fields(): void
    {
        $user = User::factory()->paid()->create([
            'role' => UserRole::Student,
            'first_name' => 'Dominique',
            'last_name' => 'Sangbo',
            'email_verified_at' => now(),
        ]);

        StudentProfile::query()->create([
            'user_id' => $user->id,
        ]);

        $this->actingAs($user)
            ->post(route('student.cv.update'), [
                'first_name' => 'Dominique',
                'last_name' => 'Sangbo',
                'current_address' => 'Bahai <b>school</b> road',
                'introduction' => 'Hello <script>alert(1)</script> world',
            ])
            ->assertRedirect(route('student.cv.edit'));

        $profile = $user->studentProfile()->first();

        $this->assertSame('Bahai school road', $profile->current_address);
        $this->assertSame('Hello alert(1) world', $profile->introduction);
        $this->assertStringNotContainsString('<', (string) $profile->current_address);
        $this->assertStringNotContainsString('<', (string) $profile->introduction);
    }

    public function test_oversized_post_returns_form_errors_instead_of_crash_page(): void
    {
        $user = User::factory()->paid()->create([
            'role' => UserRole::Student,
            'first_name' => 'Dominique',
            'last_name' => 'Sangbo',
            'email_verified_at' => now(),
        ]);

        StudentProfile::query()->create([
            'user_id' => $user->id,
        ]);

        $this->actingAs($user)
            ->withServerVariables([
                'CONTENT_LENGTH' => (string) (100 * 1024 * 1024),
            ])
            ->withHeaders([
                'X-Inertia' => 'true',
                'X-Requested-With' => 'XMLHttpRequest',
                'Accept' => 'text/html, application/xhtml+xml',
            ])
            ->post(route('student.cv.update'), [
                'first_name' => 'Dominique',
                'last_name' => 'Sangbo',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'passport_photo',
                'full_body_photo',
                'passport_document',
                'drivers_license_document',
            ]);
    }

    public function test_recruiter_and_teacher_can_view_basic_cv(): void
    {
        $student = User::factory()->paid()->create([
            'role' => UserRole::Student,
            'name' => 'Nanuka Thapa',
            'first_name' => 'Nanuka',
            'last_name' => 'Thapa',
            'email_verified_at' => now(),
            'student_number' => 'SK-COI-2024-01',
        ]);

        $profile = StudentProfile::query()->create([
            'user_id' => $student->id,
            'introduction' => 'Registered nurse.',
            'skills' => ['Leadership'],
            'vitae_data' => [
                'is_newbie' => false,
                'experiences' => [],
                'qualifications' => [],
                'trainings' => [],
                'licenses' => [],
                'bilingual' => [
                    [
                        'language' => 'English',
                        'proficiency' => 'Native',
                        'certificates' => null,
                    ],
                    [
                        'language' => 'German',
                        'proficiency' => 'Professional',
                        'certificates' => null,
                    ],
                ],
            ],
        ]);

        $recruiter = User::factory()->recruiter()->create([
            'email_verified_at' => now(),
        ]);

        $this->actingAs($recruiter)
            ->get(route('recruiter.students.show', $profile))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('app/recruiter/students/show')
                ->where('cv.candidate_id', 'SK-COI-2024-01')
                ->where('cv.introduction', 'Registered nurse.'));

        $teacher = User::factory()->teacher()->create([
            'email_verified_at' => now(),
        ]);

        $batch = Batch::factory()->create([
            'teacher_id' => $teacher->id,
        ]);

        BatchEnrollment::factory()->create([
            'batch_id' => $batch->id,
            'student_id' => $student->id,
        ]);

        $this->actingAs($teacher)
            ->get(route('recruiter.students.show', $profile))
            ->assertOk();
    }
}
