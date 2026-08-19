<?php

namespace Tests\Feature\Inquiries;

use App\Models\EducationLevel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class InquiryPhoneValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_inquiry_rejects_phone_without_country_code(): void
    {
        EducationLevel::query()->create([
            'name' => 'Graduate',
            'slug' => 'graduate',
            'sort_order' => 1,
            'is_visible' => true,
        ]);

        Storage::fake('local');

        $this->post('/inquiries', [
            'type' => 'candidate',
            'first_name' => 'Nanuka',
            'last_name' => 'Thapa',
            'date_of_birth' => '1990-01-01',
            'email' => 'nanuka@example.com',
            'phone' => '9832581198',
            'city' => 'Gangtok',
            'country' => 'India',
            'education_level' => 'graduate',
            'years_of_experience' => '2',
            'languages' => 'English',
            'qualification_certificate' => UploadedFile::fake()->create('cert.pdf', 100, 'application/pdf'),
            'resume' => UploadedFile::fake()->create('resume.pdf', 100, 'application/pdf'),
        ])->assertSessionHasErrors('phone');
    }

    public function test_inquiry_accepts_normalized_e164_phone(): void
    {
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
            'email' => 'nanuka@example.com',
            'phone' => '+91 98325 81198',
            'city' => 'Gangtok',
            'country' => 'India',
            'education_level' => 'graduate',
            'years_of_experience' => '2',
            'languages' => 'English',
            'qualification_certificate' => UploadedFile::fake()->create('cert.pdf', 100, 'application/pdf'),
            'resume' => UploadedFile::fake()->create('resume.pdf', 100, 'application/pdf'),
        ])->assertSessionDoesntHaveErrors('phone')
            ->assertRedirect();
    }
}
