<?php

namespace Tests\Feature\Student;

use App\Enums\UserRole;
use App\Models\StudentProfile;
use App\Models\User;
use App\Support\VitaeData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class StudentCvTranslateTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_user_cannot_translate_cv(): void
    {
        $this->postJson(route('student.cv.translate'), ['locale' => 'de'])
            ->assertRedirect(route('login'));
    }

    public function test_unpaid_student_cannot_translate_cv(): void
    {
        $user = User::factory()->unpaid()->create([
            'role' => UserRole::Student,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user)
            ->postJson(route('student.cv.translate'), ['locale' => 'de'])
            ->assertForbidden();
    }

    public function test_paid_student_can_translate_cv_and_cache_is_reused(): void
    {
        config([
            'services.deepl.key' => 'test-key:fx',
            'services.deepl.base_url' => 'https://api-free.deepl.com',
        ]);

        $user = User::factory()->paid()->create([
            'role' => UserRole::Student,
            'first_name' => 'Dominique',
            'last_name' => 'Sangbo',
            'email_verified_at' => now(),
        ]);

        $profile = StudentProfile::query()->create([
            'user_id' => $user->id,
            'registration_purpose' => 'training_and_placement',
            'introduction' => 'Dedicated nurse with bilingual skills.',
            'nationality' => 'Indian',
            'current_address' => 'Gangtok, Sikkim',
            'permanent_address' => 'Home street, Gangtok',
            'place_of_birth' => 'Gangtok',
            'drivers_license' => 'Two-wheeler license',
            'hobbies' => 'Reading',
            'skills' => ['Patient education', 'Leadership'],
            'vitae_data' => VitaeData::normalize([
                'is_newbie' => false,
                'experiences' => [
                    [
                        'designation' => 'Senior Nurse',
                        'department' => 'ICU',
                        'organization' => 'City Hospital',
                        'city' => 'Shillong',
                        'state' => 'Meghalaya',
                        'cases_handled' => 'Critical care cases',
                        'bed_capacity' => '200 beds',
                        'departments_offered' => 'ICU, Emergency',
                        'accreditations' => 'NABH',
                        'critical_cases' => 'Cardiac arrest',
                        'medical_equipment' => 'Ventilators',
                    ],
                ],
                'qualifications' => [
                    [
                        'title' => 'M.Sc Nursing',
                        'college' => 'Assam University',
                        'university' => 'Assam University',
                        'board' => 'State Board',
                        'language_of_instruction' => 'English',
                        'city' => 'Guwahati',
                        'state' => 'Assam',
                        'country' => 'India',
                    ],
                ],
                'certifications' => [
                    [
                        'name' => 'BLS Provider',
                        'description' => 'Basic Life Support certification',
                    ],
                ],
                'licenses' => [
                    [
                        'title' => 'Registered Nurse',
                        'registration_number' => 'RN-123',
                    ],
                ],
                'bilingual' => [
                    [
                        'language' => 'English',
                        'proficiency' => 'Professional',
                    ],
                ],
                'trainings' => [
                    [
                        'topic' => 'Emergency response workshop',
                        'organization_location' => 'City Training Centre, Shillong',
                    ],
                ],
                'seminars' => [
                    [
                        'topic' => 'Nursing leadership seminar',
                        'organization' => 'State Nursing Council',
                        'location' => 'Guwahati',
                    ],
                ],
            ]),
        ]);

        $requestCount = 0;

        Http::fake(function (Request $request) use (&$requestCount) {
            $requestCount++;

            $this->assertSame('https://api-free.deepl.com/v2/translate', $request->url());
            $this->assertSame(
                'DeepL-Auth-Key test-key:fx',
                $request->header('Authorization')[0] ?? null,
            );
            $this->assertSame('EN', $request['source_lang']);
            $this->assertSame('DE', $request['target_lang']);

            $texts = $request['text'];
            $this->assertIsArray($texts);
            $this->assertNotEmpty($texts);

            return Http::response([
                'translations' => array_map(
                    fn (string $text): array => [
                        'detected_source_language' => 'EN',
                        'text' => "DE:{$text}",
                    ],
                    $texts,
                ),
            ]);
        });

        $first = $this->actingAs($user)
            ->postJson(route('student.cv.translate'), ['locale' => 'de'])
            ->assertOk()
            ->json('cv');

        $this->assertSame('DE:Dedicated nurse with bilingual skills.', $first['introduction']);
        $this->assertSame('DE:Indian', $first['nationality']);
        $this->assertSame('DE:Home street, Gangtok', $first['permanent_address']);
        $this->assertSame('DE:Two-wheeler license', $first['drivers_license']);
        $this->assertSame('DE:Senior Nurse', $first['vitae']['experiences'][0]['designation']);
        $this->assertSame('DE:200 beds', $first['vitae']['experiences'][0]['bed_capacity']);
        $this->assertSame('DE:NABH', $first['vitae']['experiences'][0]['accreditations']);
        $this->assertSame('DE:English', $first['vitae']['qualifications'][0]['language_of_instruction']);
        $this->assertSame('DE:City Training Centre, Shillong', $first['vitae']['trainings'][0]['organization_location']);
        $this->assertSame('DE:State Nursing Council', $first['vitae']['seminars'][0]['organization']);
        $this->assertSame('DE:Registered Nurse', $first['vitae']['licenses'][0]['title']);
        $this->assertSame('RN-123', $first['vitae']['licenses'][0]['registration_number']);
        $this->assertSame(1, $requestCount);

        $profile->refresh();
        $this->assertIsArray($profile->cv_translation_cache);
        $this->assertArrayHasKey('de', $profile->cv_translation_cache);
        $this->assertArrayHasKey('hash', $profile->cv_translation_cache['de']);
        $this->assertArrayHasKey('fields', $profile->cv_translation_cache['de']);

        $second = $this->actingAs($user)
            ->postJson(route('student.cv.translate'), ['locale' => 'de'])
            ->assertOk()
            ->json('cv');

        $this->assertSame($first['introduction'], $second['introduction']);
        $this->assertSame(1, $requestCount);
    }

    public function test_cv_update_clears_translation_cache(): void
    {
        config([
            'services.deepl.key' => 'test-key:fx',
            'services.deepl.base_url' => 'https://api-free.deepl.com',
        ]);

        $user = User::factory()->paid()->create([
            'role' => UserRole::Student,
            'first_name' => 'Dominique',
            'last_name' => 'Sangbo',
            'email_verified_at' => now(),
        ]);

        $profile = StudentProfile::query()->create([
            'user_id' => $user->id,
            'registration_purpose' => 'training_and_placement',
            'mobile_whatsapp' => '+911234567890',
            'introduction' => 'Original introduction',
            'skills' => ['Leadership'],
            'vitae_data' => VitaeData::normalize([
                'is_newbie' => true,
                'bilingual' => [
                    [
                        'language' => 'English',
                        'proficiency' => 'Native',
                    ],
                ],
            ]),
            'cv_translation_cache' => [
                'de' => [
                    'hash' => 'stale-hash',
                    'translated_at' => now()->toIso8601String(),
                    'fields' => [
                        'introduction' => 'Alte Einleitung',
                    ],
                ],
            ],
        ]);

        $this->actingAs($user)->post(route('student.cv.update'), [
            'first_name' => 'Dominique',
            'last_name' => 'Sangbo',
            'mobile_whatsapp' => '+911234567890',
            'introduction' => 'Updated introduction',
            'skills' => ['Leadership'],
            'vitae' => [
                'is_newbie' => true,
                'experiences' => [],
                'qualifications' => [],
                'trainings' => [],
                'seminars' => [],
                'presentations' => [],
                'publications' => [],
                'certifications' => [],
                'licenses' => [],
                'bilingual' => [
                    [
                        'language' => 'English',
                        'proficiency' => 'Native',
                    ],
                ],
            ],
        ])->assertRedirect(route('student.cv.edit'));

        $profile->refresh();
        $this->assertNull($profile->cv_translation_cache);

        $requestCount = 0;

        Http::fake(function (Request $request) use (&$requestCount) {
            $requestCount++;
            $texts = $request['text'];

            return Http::response([
                'translations' => array_map(
                    fn (string $text): array => [
                        'detected_source_language' => 'EN',
                        'text' => "DE:{$text}",
                    ],
                    is_array($texts) ? $texts : [$texts],
                ),
            ]);
        });

        $this->actingAs($user)
            ->postJson(route('student.cv.translate'), ['locale' => 'de'])
            ->assertOk()
            ->assertJsonPath('cv.introduction', 'DE:Updated introduction');

        $this->assertSame(1, $requestCount);
    }

    public function test_translate_requires_configured_deepl_key(): void
    {
        config([
            'services.deepl.key' => null,
            'services.deepl.base_url' => 'https://api-free.deepl.com',
        ]);

        $user = User::factory()->paid()->create([
            'role' => UserRole::Student,
            'email_verified_at' => now(),
        ]);

        StudentProfile::query()->create([
            'user_id' => $user->id,
            'registration_purpose' => 'training_and_placement',
            'introduction' => 'Hello world',
        ]);

        $this->actingAs($user)
            ->postJson(route('student.cv.translate'), ['locale' => 'de'])
            ->assertStatus(422);
    }

    public function test_recruiter_can_translate_student_cv(): void
    {
        config([
            'services.deepl.key' => 'test-key:fx',
            'services.deepl.base_url' => 'https://api-free.deepl.com',
        ]);

        $student = User::factory()->paid()->create([
            'role' => UserRole::Student,
            'first_name' => 'Dominique',
            'last_name' => 'Sangbo',
            'email_verified_at' => now(),
        ]);

        $profile = StudentProfile::query()->create([
            'user_id' => $student->id,
            'registration_purpose' => 'training_and_placement',
            'introduction' => 'Dedicated nurse with bilingual skills.',
            'permanent_address' => 'Home street, Gangtok',
            'vitae_data' => VitaeData::normalize([
                'is_newbie' => true,
                'experiences' => [],
            ]),
        ]);

        $recruiter = User::factory()->recruiter()->create([
            'email_verified_at' => now(),
        ]);

        Http::fake(function (Request $request) {
            $texts = $request['text'];

            return Http::response([
                'translations' => array_map(
                    fn (string $text): array => [
                        'detected_source_language' => 'EN',
                        'text' => "DE:{$text}",
                    ],
                    is_array($texts) ? $texts : [$texts],
                ),
            ]);
        });

        $this->actingAs($recruiter)
            ->postJson(route('recruiter.students.cv.translate', $profile), ['locale' => 'de'])
            ->assertOk()
            ->assertJsonPath('cv.introduction', 'DE:Dedicated nurse with bilingual skills.')
            ->assertJsonPath('cv.permanent_address', 'DE:Home street, Gangtok');
    }
}
