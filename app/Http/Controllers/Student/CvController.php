<?php

namespace App\Http\Controllers\Student;

use App\Enums\CivilStatus;
use App\Enums\Gender;
use App\Enums\SpecialId;
use App\Http\Controllers\Controller;
use App\Http\Requests\Student\UpdateStudentCvRequest;
use App\Models\StudentProfile;
use App\Services\CvTranslationService;
use App\Support\CvProfilePresenter;
use App\Support\IndianStates;
use App\Support\VitaeData;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class CvController extends Controller
{
    public function edit(): Response
    {
        $user = request()->user();
        $profile = StudentProfile::query()->firstOrCreate(['user_id' => $user->id]);

        return Inertia::render('app/student/cv', [
            'cv' => CvProfilePresenter::present($user, $profile),
            'options' => [
                'civilStatuses' => CivilStatus::values(),
                'genders' => Gender::values(),
                'states' => IndianStates::names(),
                'specialIds' => collect(SpecialId::cases())
                    ->map(fn (SpecialId $id): array => [
                        'value' => $id->value,
                        'label' => $id->label(),
                    ])
                    ->values()
                    ->all(),
            ],
        ]);
    }

    public function update(UpdateStudentCvRequest $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        DB::transaction(function () use ($request, $user, $validated): void {
            $profile = StudentProfile::query()->firstOrCreate(['user_id' => $user->id]);

            $firstName = trim($validated['first_name']);
            $lastName = trim($validated['last_name']);

            $user->forceFill([
                'first_name' => $firstName,
                'last_name' => $lastName,
                'name' => trim("{$firstName} {$lastName}"),
            ])->save();

            $passportPath = $profile->passport_photo_path;
            $fullBodyPath = $profile->full_body_photo_path;
            $passportDocumentPath = $profile->passport_document_path;
            $driversLicenseDocumentPath = $profile->drivers_license_document_path;

            if ($request->hasFile('passport_photo')) {
                $passportPath = $this->storePhoto(
                    $request->file('passport_photo'),
                    $user->id,
                    'passport',
                    $passportPath,
                );
            }

            if ($request->hasFile('full_body_photo')) {
                $fullBodyPath = $this->storePhoto(
                    $request->file('full_body_photo'),
                    $user->id,
                    'full-body',
                    $fullBodyPath,
                );
            }

            if ($request->hasFile('passport_document')) {
                $passportDocumentPath = $this->storePhoto(
                    $request->file('passport_document'),
                    $user->id,
                    'passport-document',
                    $passportDocumentPath,
                );
            }

            if ($request->hasFile('drivers_license_document')) {
                $driversLicenseDocumentPath = $this->storePhoto(
                    $request->file('drivers_license_document'),
                    $user->id,
                    'drivers-license-document',
                    $driversLicenseDocumentPath,
                );
            }

            $vitaeInput = is_array($validated['vitae'] ?? null) ? $validated['vitae'] : [];
            $existingVitae = VitaeData::normalize($profile->vitae_data);
            $vitae = $this->mergeVitaeWithUploads($request, $user->id, $vitaeInput, $existingVitae);

            $languages = collect($vitae['bilingual'] ?? [])
                ->map(function (array $entry): ?string {
                    $language = filled($entry['language'] ?? null)
                        ? (string) $entry['language']
                        : null;
                    $level = filled($entry['proficiency'] ?? null)
                        ? (string) $entry['proficiency']
                        : null;

                    if ($language !== null && $level !== null) {
                        return "{$language} — {$level}";
                    }

                    return $language ?? $level;
                })
                ->filter()
                ->values()
                ->all();

            $profile->fill([
                'mobile_whatsapp' => $validated['mobile_whatsapp'] ?? null,
                'date_of_birth' => $validated['date_of_birth'] ?? null,
                'place_of_birth' => $validated['place_of_birth'] ?? null,
                'civil_status' => $validated['civil_status'] ?? null,
                'gender' => $validated['gender'] ?? null,
                'secondary_email' => $validated['secondary_email'] ?? null,
                'secondary_mobile' => $validated['secondary_mobile'] ?? null,
                'current_address' => $validated['current_address'] ?? null,
                'permanent_address' => $validated['permanent_address'] ?? null,
                'nationality' => $validated['nationality'] ?? null,
                'passport_or_id_number' => $validated['passport_or_id_number'] ?? null,
                'passport_document_path' => $passportDocumentPath,
                'drivers_license' => $validated['drivers_license'] ?? null,
                'drivers_license_document_path' => $driversLicenseDocumentPath,
                'passport_photo_path' => $passportPath,
                'full_body_photo_path' => $fullBodyPath,
                'introduction' => $validated['introduction'] ?? null,
                'hobbies' => $validated['hobbies'] ?? null,
                'skills' => $validated['skills'] ?? [],
                'gap_reasons' => $validated['gap_reasons'] ?? null,
                'current_status' => $validated['current_status'] ?? null,
                'pursuing_details' => $validated['pursuing_details'] ?? null,
                'headline' => $validated['headline'] ?? null,
                'languages' => $languages,
                'proficiency_summary' => $this->buildProficiencySummary($vitae['bilingual'] ?? []),
                'vitae_data' => $vitae,
                'cv_translation_cache' => null,
            ])->save();
        });

        return redirect()
            ->route('student.cv.edit')
            ->with('success', 'Your CV has been saved.');
    }

    public function translate(Request $request, CvTranslationService $translations): JsonResponse
    {
        $validated = $request->validate([
            'locale' => ['required', 'string', 'in:de'],
        ]);

        $user = $request->user();
        $profile = StudentProfile::query()->firstOrCreate(['user_id' => $user->id]);

        try {
            $cv = $translations->translateForPrint(
                $user,
                $profile,
                $validated['locale'],
            );
        } catch (RuntimeException $exception) {
            $message = $exception->getMessage();

            if (str_contains($message, 'not configured')) {
                abort(422, 'German CV translation is not configured.');
            }

            abort(502, 'Unable to prepare the German CV right now. Please try again.');
        }

        return response()->json([
            'cv' => $cv,
        ]);
    }

    private function storePhoto(UploadedFile $file, int $userId, string $kind, ?string $previous): string
    {
        if (filled($previous)) {
            Storage::disk('public')->delete($previous);
        }

        return $file->store("cv/{$userId}/{$kind}", 'public');
    }

    /**
     * @param  array<string, mixed>  $vitaeInput
     * @param  array<string, mixed>  $existing
     * @return array<string, mixed>
     */
    private function mergeVitaeWithUploads(
        UpdateStudentCvRequest $request,
        int $userId,
        array $vitaeInput,
        array $existing,
    ): array {
        $experiences = [];
        foreach ($vitaeInput['experiences'] ?? [] as $index => $row) {
            if (! is_array($row)) {
                continue;
            }
            $merged = array_merge(VitaeData::emptyExperience(), $row);
            unset($merged['certificate']);
            $existingPath = $existing['experiences'][$index]['certificate_path'] ?? null;
            $merged['certificate_path'] = $existingPath;
            $merged['has_experience_letter'] = filter_var(
                $row['has_experience_letter'] ?? true,
                FILTER_VALIDATE_BOOLEAN,
            );

            if ($request->hasFile("vitae.experiences.{$index}.certificate")) {
                $merged['certificate_path'] = $this->storePhoto(
                    $request->file("vitae.experiences.{$index}.certificate"),
                    $userId,
                    "experience-{$index}",
                    is_string($existingPath) ? $existingPath : null,
                );
            }

            $experiences[] = $merged;
        }

        $qualifications = [];
        foreach ($vitaeInput['qualifications'] ?? [] as $row) {
            if (! is_array($row)) {
                continue;
            }
            $qualifications[] = array_merge(VitaeData::emptyQualification(), $row);
        }

        $trainings = [];
        foreach ($vitaeInput['trainings'] ?? [] as $index => $row) {
            if (! is_array($row)) {
                continue;
            }
            $merged = array_merge(VitaeData::emptyTraining(), $row);
            unset($merged['certificate']);
            $existingPath = $existing['trainings'][$index]['certificate_path'] ?? null;
            $merged['certificate_path'] = $existingPath;

            if ($request->hasFile("vitae.trainings.{$index}.certificate")) {
                $merged['certificate_path'] = $this->storePhoto(
                    $request->file("vitae.trainings.{$index}.certificate"),
                    $userId,
                    "training-{$index}",
                    is_string($existingPath) ? $existingPath : null,
                );
            }

            $trainings[] = $merged;
        }

        $seminars = $this->mergeActivityEntries(
            $request,
            $userId,
            $vitaeInput['seminars'] ?? [],
            $existing['seminars'] ?? [],
            'seminars',
        );
        $presentations = $this->mergeActivityEntries(
            $request,
            $userId,
            $vitaeInput['presentations'] ?? [],
            $existing['presentations'] ?? [],
            'presentations',
        );
        $publications = $this->mergeActivityEntries(
            $request,
            $userId,
            $vitaeInput['publications'] ?? [],
            $existing['publications'] ?? [],
            'publications',
        );

        $certifications = [];
        foreach ($vitaeInput['certifications'] ?? [] as $index => $row) {
            if (! is_array($row)) {
                continue;
            }
            $merged = array_merge(VitaeData::emptyCertification(), $row);
            unset($merged['certificate']);
            $existingPath = $existing['certifications'][$index]['certificate_path'] ?? null;
            $merged['certificate_path'] = $existingPath;

            if ($request->hasFile("vitae.certifications.{$index}.certificate")) {
                $merged['certificate_path'] = $this->storePhoto(
                    $request->file("vitae.certifications.{$index}.certificate"),
                    $userId,
                    "certification-{$index}",
                    is_string($existingPath) ? $existingPath : null,
                );
            }

            $certifications[] = $merged;
        }

        $licenses = [];
        foreach ($vitaeInput['licenses'] ?? [] as $index => $row) {
            if (! is_array($row)) {
                continue;
            }
            $merged = array_merge(VitaeData::emptyLicense(), $row);
            unset($merged['certificate']);
            $existingPath = $existing['licenses'][$index]['certificate_path'] ?? null;
            $merged['certificate_path'] = $existingPath;

            if ($request->hasFile("vitae.licenses.{$index}.certificate")) {
                $merged['certificate_path'] = $this->storePhoto(
                    $request->file("vitae.licenses.{$index}.certificate"),
                    $userId,
                    "license-{$index}",
                    is_string($existingPath) ? $existingPath : null,
                );
            }

            $licenses[] = $merged;
        }

        $bilingual = [];
        foreach ($vitaeInput['bilingual'] ?? [] as $index => $row) {
            if (! is_array($row)) {
                continue;
            }
            $merged = array_merge(VitaeData::emptyBilingualEntry(), $row);
            unset($merged['certificate']);
            $existingPath = $existing['bilingual'][$index]['certificate_path'] ?? null;
            $merged['certificate_path'] = $existingPath;

            if ($request->hasFile("vitae.bilingual.{$index}.certificate")) {
                $merged['certificate_path'] = $this->storePhoto(
                    $request->file("vitae.bilingual.{$index}.certificate"),
                    $userId,
                    "bilingual-{$index}",
                    is_string($existingPath) ? $existingPath : null,
                );
            }

            if (($merged['language_type'] ?? null) === 'Regional') {
                $merged['certification_level'] = null;
            }

            $bilingual[] = $merged;
        }

        return VitaeData::normalize([
            'is_newbie' => filter_var($vitaeInput['is_newbie'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'experiences' => $experiences,
            'qualifications' => $qualifications,
            'trainings' => $trainings,
            'seminars' => $seminars,
            'presentations' => $presentations,
            'publications' => $publications,
            'certifications' => $certifications,
            'licenses' => $licenses,
            'bilingual' => $bilingual,
        ]);
    }

    /**
     * @param  list<mixed>  $rows
     * @param  list<array<string, mixed>>  $existingRows
     * @return list<array<string, mixed>>
     */
    private function mergeActivityEntries(
        Request $request,
        int $userId,
        array $rows,
        array $existingRows,
        string $key,
    ): array {
        $entries = [];

        foreach ($rows as $index => $row) {
            if (! is_array($row)) {
                continue;
            }

            $merged = array_merge(VitaeData::emptyActivityEntry(), $row);
            unset($merged['certificate']);
            $existingPath = $existingRows[$index]['certificate_path'] ?? null;
            $merged['certificate_path'] = $existingPath;

            $evidenceType = $merged['evidence_type'] ?? null;
            if ($evidenceType === 'link') {
                $merged['certificate_path'] = null;
            } elseif ($evidenceType === 'file') {
                $merged['link_url'] = null;
            }

            if ($evidenceType === 'file' && $request->hasFile("vitae.{$key}.{$index}.certificate")) {
                $merged['certificate_path'] = $this->storePhoto(
                    $request->file("vitae.{$key}.{$index}.certificate"),
                    $userId,
                    "{$key}-{$index}",
                    is_string($existingPath) ? $existingPath : null,
                );
            }

            $entries[] = $merged;
        }

        return $entries;
    }

    /**
     * @param  list<array<string, mixed>>  $bilingual
     */
    private function buildProficiencySummary(array $bilingual): ?string
    {
        $parts = collect($bilingual)
            ->map(function (array $entry): ?string {
                $language = filled($entry['language'] ?? null)
                    ? (string) $entry['language']
                    : null;
                $level = filled($entry['proficiency'] ?? null)
                    ? (string) $entry['proficiency']
                    : null;

                if ($language !== null && $level !== null) {
                    return "{$language} — {$level}";
                }

                return $language ?? $level;
            })
            ->filter()
            ->values()
            ->all();

        return $parts === [] ? null : implode(' · ', $parts);
    }
}
