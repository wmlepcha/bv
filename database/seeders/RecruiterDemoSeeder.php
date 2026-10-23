<?php

namespace Database\Seeders;

use App\Enums\CourseLevel;
use App\Enums\Profession;
use App\Enums\UserRole;
use App\Models\Batch;
use App\Models\BatchEnrollment;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

/**
 * Demo candidate with a complete CV and uploaded documents, for trying out the recruiter portal.
 * Local/demo use only: all values and files (database/seeders/demo-files) are fictional.
 */
class RecruiterDemoSeeder extends Seeder
{
    public const EMAIL = 'wmlepcha15@gmail.com';

    public const DOMINIQUE_EMAIL = 'dom@bv.com';

    public const RECRUITMENT_MANAGER_EMAIL = 'recruitment.manager@bilingualvitae.com';

    public function run(): void
    {
        if (app()->isProduction()) {
            $this->command?->error('RecruiterDemoSeeder must not run in production.');

            return;
        }

        $this->seedRapgyal();
        $this->seedDominique();
        $this->seedRecruitmentManager();
    }

    /**
     * Demo placement manager. Password is "password"; this seeder never runs in production.
     */
    public function seedRecruitmentManager(): void
    {
        $manager = User::query()->firstOrNew(['email' => self::RECRUITMENT_MANAGER_EMAIL]);

        if (! $manager->exists) {
            $manager->forceFill([
                'name' => 'Recruitment Manager',
                'password' => Hash::make('password'),
                'role' => UserRole::RecruitmentManager,
                'email_verified_at' => now(),
                'registration_paid_at' => now(),
            ])->save();
        }
    }

    private function seedRapgyal(): void
    {

        $user = User::query()->firstOrCreate(
            ['email' => self::EMAIL],
            [
                'name' => 'Rapgyal Lepcha',
                'first_name' => 'Rapgyal',
                'last_name' => 'Lepcha',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'role' => UserRole::Student,
                'student_number' => 'SK-DEMO-2026-01',
                'registration_paid_at' => now(),
                'dashboard_activated_at' => now(),
                'registration_expires_at' => now()->addYears(2),
            ],
        );

        $batch = Batch::query()->where('level', CourseLevel::A1)->where('is_active', true)->first()
            ?? Batch::factory()->create(['level' => CourseLevel::A1]);

        BatchEnrollment::query()->firstOrCreate(['student_id' => $user->id], ['batch_id' => $batch->id]);

        $paths = $this->copyDemoFiles($user->id, 'rapgyal');

        StudentProfile::query()->updateOrCreate(['user_id' => $user->id], [
            'registration_purpose' => 'training_and_placement',
            'headline' => 'Registered Nurse | 3 years ICU experience | German A1',
            'introduction' => 'Dedicated registered nurse with three years of critical-care experience, currently learning German to work in healthcare in Germany. Calm under pressure, patient-focused and a reliable team player.',
            'mobile_whatsapp' => '+919800000002',
            'secondary_mobile' => '+919800000003',
            'date_of_birth' => '1999-04-12',
            'place_of_birth' => 'Gangtok',
            'civil_status' => 'Unmarried',
            'gender' => 'Male',
            'nationality' => 'Indian',
            'current_address' => 'Development Area, Gangtok, Sikkim 737101, India',
            'permanent_address' => 'Development Area, Gangtok, Sikkim 737101, India',
            'hobbies' => 'Trekking, football, photography',
            'skills' => ['Patient assessment', 'IV cannulation', 'Ventilator care', 'Wound dressing', 'Emergency response', 'Team communication'],
            'languages' => ['English', 'Hindi', 'Nepali', 'Lepcha', 'German (A1)'],
            'proficiency_summary' => 'English fluent, Hindi and Nepali native, German A1 (in progress)',
            'current_status' => 'Working',
            'professions' => [Profession::NurseRegistration->value],
            'passport_photo_path' => $paths['passport/demo-passport.jpg'],
            'full_body_photo_path' => $paths['full_body/demo-full-body.jpg'],
            'passport_document_path' => $paths['passport/demo-passport-document.pdf'],
            'vitae_data' => $this->vitae($paths),
        ]);
    }

    /**
     * @return array<string, string> demo-files relative path => stored public-disk path
     */
    private function copyDemoFiles(int $userId, string $folder): array
    {
        $root = database_path("seeders/demo-files/{$folder}");
        $paths = [];

        foreach (glob($root.'/*/*') ?: [] as $file) {
            $relative = basename(dirname($file)).'/'.basename($file);
            $paths[$relative] = "cv/{$userId}/{$relative}";
            $disk = str_contains($relative, 'passport-document') ? StudentProfile::PRIVATE_DISK : 'public';
            Storage::disk($disk)->put($paths[$relative], (string) file_get_contents($file));
        }

        return $paths;
    }

    /**
     * @param  array<string, string>  $paths
     * @return array<string, mixed>
     */
    private function vitae(array $paths): array
    {
        return [
            'is_newbie' => false,
            'experiences' => [[
                'duration' => '3 years', 'start_date' => '2022-06-01', 'end_date' => '2025-06-30',
                'designation' => 'Staff Nurse', 'department' => 'Intensive Care Unit', 'organization' => 'STNM Multispecialty Hospital',
                'city' => 'Gangtok', 'state' => 'Sikkim', 'cases_handled' => 'Ventilated and post-operative patients, cardiac monitoring',
                'bed_capacity' => '200', 'medical_equipment' => 'Ventilators, cardiac monitors, infusion pumps, defibrillator',
                'departments_offered' => 'ICU, Cardiology, Orthopaedics, Maternity', 'accreditations' => 'NABH',
                'critical_cases' => 'Sepsis, respiratory failure, post-cardiac surgery', 'has_experience_letter' => true,
                'certificate_path' => $paths['experience/demo-experience-letter.pdf'],
            ]],
            'qualifications' => [[
                'start_date' => '2018-08-01', 'end_date' => '2022-05-31', 'title' => 'B.Sc. Nursing',
                'college' => 'Sikkim Manipal College of Nursing', 'university' => 'Sikkim Manipal University',
                'city' => 'Gangtok', 'state' => 'Sikkim', 'country' => 'India', 'board' => null, 'language_of_instruction' => 'English',
                'certificate_path' => $paths['qualifications/demo-bsc-nursing-degree.pdf'],
            ], [
                'start_date' => '2016-04-01', 'end_date' => '2018-03-31', 'title' => 'Class XII (Science)',
                'college' => 'Tashi Namgyal Academy', 'university' => null,
                'city' => 'Gangtok', 'state' => 'Sikkim', 'country' => 'India', 'board' => 'CBSE', 'language_of_instruction' => 'English',
                'certificate_path' => $paths['qualifications/demo-class-xii-marksheet.pdf'],
            ]],
            'trainings' => [[
                'date' => '2023-03-15', 'topic' => 'Advanced Cardiac Life Support (ACLS)', 'organization_location' => 'Gangtok',
                'certificate_path' => $paths['trainings/demo-acls-certificate.pdf'],
            ]],
            'seminars' => [[
                'date' => '2024-01-20', 'topic' => 'Critical Care Nursing Update', 'organization' => 'Indian Society of Critical Care Medicine',
                'location' => 'Kolkata', 'evidence_type' => 'link', 'certificate_path' => null, 'link_url' => 'https://www.isccm.org',
            ]],
            'presentations' => [],
            'publications' => [],
            'certifications' => [[
                'name' => 'Basic Life Support (BLS)', 'description' => 'American Heart Association provider certification',
                'certificate_path' => $paths['certifications/demo-bls-certificate.pdf'],
            ]],
            'licenses' => [[
                'title' => 'Registered Nurse & Midwife', 'registration_number' => 'SNC-2022-04512',
                'issued_date' => '2022-08-10', 'valid_till' => '2027-08-09',
                'certificate_path' => $paths['licenses/demo-nursing-license.pdf'],
            ]],
            'bilingual' => [
                ['language' => 'English', 'language_type' => 'Second language', 'proficiency' => 'Fluent', 'certification_level' => null, 'certificates' => null, 'certificate_path' => null],
                ['language' => 'German', 'language_type' => 'Foreign language', 'proficiency' => 'Beginner', 'certification_level' => 'A1 (in progress)', 'certificates' => null, 'certificate_path' => $paths['languages/demo-german-a1-certificate.pdf']],
            ],
        ];
    }

    /**
     * Completes the existing Dominique Sangbo candidate (dom@bv.com): keeps what he entered himself
     * (introduction, address, phone, photos) and only fills blanks plus the CV sections and documents.
     */
    private function seedDominique(): void
    {
        $user = User::query()->firstOrCreate(
            ['email' => self::DOMINIQUE_EMAIL],
            [
                'name' => 'Dominique Sangbo',
                'first_name' => 'Dominique',
                'last_name' => 'Sangbo',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'role' => UserRole::Student,
                'student_number' => 'SK-COI-2026-01',
                'registration_paid_at' => now(),
                'dashboard_activated_at' => now(),
                'registration_expires_at' => now()->addYears(2),
            ],
        );

        $batch = Batch::query()->where('level', CourseLevel::A1)->where('is_active', true)->first()
            ?? Batch::factory()->create(['level' => CourseLevel::A1]);
        BatchEnrollment::query()->firstOrCreate(['student_id' => $user->id], ['batch_id' => $batch->id]);

        $paths = $this->copyDemoFiles($user->id, 'dominique');
        $profile = StudentProfile::query()->firstOrNew(['user_id' => $user->id]);
        $profile->registration_purpose = 'training_and_placement';

        $fillIfBlank = [
            'introduction' => 'Experienced registered nurse with over ten years in paediatric care, nursing education and public health, now preparing to work in German healthcare.',
            'mobile_whatsapp' => '7872012752',
            'date_of_birth' => '1991-07-16',
            'place_of_birth' => 'Pakyong, Sikkim',
            'civil_status' => 'Married',
            'gender' => 'Male',
            'nationality' => 'Indian',
            'current_address' => 'Below Swastik gate, Gangtok Sikkim',
            'permanent_address' => 'Below Swastik gate, Gangtok Sikkim',
            'hobbies' => 'Playing games, reading',
            'current_status' => 'Studying',
            'pursuing_details' => 'Pursuing Bachelor of Computer Application',
            'secondary_mobile' => '+919800000004',
            'passport_or_id_number' => 'P7654321',
            'drivers_license' => 'SK-07-20150001234',
        ];

        foreach ($fillIfBlank as $attribute => $value) {
            if (blank($profile->{$attribute})) {
                $profile->{$attribute} = $value;
            }
        }

        $profile->forceFill([
            'headline' => 'Registered Nurse | 10+ years | M.Sc. Child Health Nursing | German A1',
            'skills' => ['Paediatric nursing', 'Neonatal care', 'Patient education', 'Community health', 'Clinical teaching', 'Counselling'],
            'languages' => ['English', 'Hindi', 'Nepali', 'Sikkimese', 'German (A1)'],
            'proficiency_summary' => 'English fluent, Hindi and Nepali native, German A1 (in progress)',
            'professions' => [Profession::NurseRegistration->value],
            'passport_document_path' => $paths['passport/demo-passport-document.pdf'],
            'drivers_license_document_path' => $paths['drivers/demo-drivers-license.pdf'],
            'vitae_data' => $this->dominiqueVitae($paths),
        ])->save();
    }

    /**
     * @param  array<string, string>  $paths
     * @return array<string, mixed>
     */
    private function dominiqueVitae(array $paths): array
    {
        $experience = fn (array $row, string $file): array => $row + [
            'has_experience_letter' => true,
            'certificate_path' => $paths[$file],
        ];

        return [
            'is_newbie' => false,
            'experiences' => [
                $experience([
                    'duration' => '3 years', 'start_date' => '2013-06-01', 'end_date' => '2016-05-31',
                    'designation' => 'Staff Nurse (Paediatrics)', 'department' => 'Paediatric ward', 'organization' => 'STNM Multispecialty Hospital',
                    'city' => 'Gangtok', 'state' => 'Sikkim', 'cases_handled' => 'Childhood infections, respiratory illness, neonatal jaundice',
                    'bed_capacity' => '200', 'medical_equipment' => 'Infusion pumps, nebulisers, phototherapy units, pulse oximeters',
                    'departments_offered' => 'Paediatrics, ICU, Maternity, Orthopaedics', 'accreditations' => 'NABH',
                    'critical_cases' => 'Neonatal sepsis, severe dehydration, status asthmaticus',
                ], 'experience/demo-experience-staff-nurse.pdf'),
                $experience([
                    'duration' => '3.5 years', 'start_date' => '2018-06-01', 'end_date' => '2021-12-31',
                    'designation' => 'Nursing Tutor', 'department' => 'Child Health Nursing', 'organization' => 'Sikkim Manipal College of Nursing',
                    'city' => 'Gangtok', 'state' => 'Sikkim', 'cases_handled' => 'Taught and supervised B.Sc. Nursing students in paediatric clinical placements',
                    'bed_capacity' => null, 'medical_equipment' => 'Skills laboratory and simulation manikins',
                    'departments_offered' => null, 'accreditations' => 'INC', 'critical_cases' => null,
                ], 'experience/demo-experience-nursing-tutor.pdf'),
                $experience([
                    'duration' => '3.7 years', 'start_date' => '2022-01-01', 'end_date' => '2025-09-30',
                    'designation' => 'Public Health Nurse', 'department' => 'Community health', 'organization' => 'Community Health Centre, Pakyong',
                    'city' => 'Pakyong', 'state' => 'Sikkim', 'cases_handled' => 'Immunisation drives, maternal and child health clinics, school health screening',
                    'bed_capacity' => '30', 'medical_equipment' => 'Vaccine cold chain, basic diagnostic kits',
                    'departments_offered' => 'OPD, Maternity, Immunisation', 'accreditations' => 'National Health Mission',
                    'critical_cases' => 'Outbreak response and referral of high-risk pregnancies',
                ], 'experience/demo-experience-public-health.pdf'),
            ],
            'qualifications' => [
                ['start_date' => '2020-08-01', 'end_date' => '2022-06-30', 'title' => 'M.Sc. Psychology', 'college' => 'Department of Psychology', 'university' => 'Sikkim University', 'city' => 'Gangtok', 'state' => 'Sikkim', 'country' => 'India', 'board' => null, 'language_of_instruction' => 'English', 'certificate_path' => $paths['qualifications/demo-msc-psychology-degree.pdf']],
                ['start_date' => '2016-08-01', 'end_date' => '2018-05-31', 'title' => 'M.Sc. Child Health (Paediatric) Nursing', 'college' => 'Sikkim Manipal College of Nursing', 'university' => 'Sikkim Manipal University', 'city' => 'Gangtok', 'state' => 'Sikkim', 'country' => 'India', 'board' => null, 'language_of_instruction' => 'English', 'certificate_path' => $paths['qualifications/demo-msc-child-health-nursing.pdf']],
                ['start_date' => '2009-08-01', 'end_date' => '2013-05-31', 'title' => 'B.Sc. Nursing', 'college' => 'Sikkim Manipal College of Nursing', 'university' => 'Sikkim Manipal University', 'city' => 'Gangtok', 'state' => 'Sikkim', 'country' => 'India', 'board' => null, 'language_of_instruction' => 'English', 'certificate_path' => $paths['qualifications/demo-bsc-nursing-degree.pdf']],
                ['start_date' => '2007-04-01', 'end_date' => '2009-03-31', 'title' => 'Class XII (Science)', 'college' => 'Tashi Namgyal Academy', 'university' => null, 'city' => 'Gangtok', 'state' => 'Sikkim', 'country' => 'India', 'board' => 'CBSE', 'language_of_instruction' => 'English', 'certificate_path' => $paths['qualifications/demo-class-xii-marksheet.pdf']],
            ],
            'trainings' => [
                ['date' => '2019-08-12', 'topic' => 'Paediatric Advanced Life Support (PALS)', 'organization_location' => 'Gangtok', 'certificate_path' => $paths['trainings/demo-pals-certificate.pdf']],
                ['date' => '2015-03-03', 'topic' => 'Neonatal Intensive Care (NICU) Training', 'organization_location' => 'Siliguri', 'certificate_path' => $paths['trainings/demo-nicu-training.pdf']],
            ],
            'seminars' => [[
                'date' => '2023-02-18', 'topic' => 'Child Health and Community Nursing Conference', 'organization' => 'Trained Nurses Association of India',
                'location' => 'Guwahati', 'evidence_type' => 'link', 'certificate_path' => null, 'link_url' => 'https://tnaionline.org',
            ]],
            'presentations' => [],
            'publications' => [],
            'certifications' => [[
                'name' => 'Basic Life Support (BLS)', 'description' => 'American Heart Association provider certification',
                'certificate_path' => $paths['certifications/demo-bls-certificate.pdf'],
            ]],
            'licenses' => [[
                'title' => 'Registered Nurse & Midwife', 'registration_number' => 'SNC-2013-00871',
                'issued_date' => '2013-08-05', 'valid_till' => '2028-06-30',
                'certificate_path' => $paths['licenses/demo-nursing-license.pdf'],
            ]],
            'bilingual' => [
                ['language' => 'English', 'language_type' => 'Second language', 'proficiency' => 'Fluent', 'certification_level' => null, 'certificates' => null, 'certificate_path' => null],
                ['language' => 'German', 'language_type' => 'Foreign language', 'proficiency' => 'Beginner', 'certification_level' => 'A1 (in progress)', 'certificates' => null, 'certificate_path' => $paths['languages/demo-german-a1-certificate.pdf']],
            ],
        ];
    }
}
