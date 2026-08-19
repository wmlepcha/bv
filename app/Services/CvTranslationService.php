<?php

namespace App\Services;

use App\Models\StudentProfile;
use App\Models\User;
use App\Support\CvProfilePresenter;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class CvTranslationService
{
    public const LOCALE_DE = 'de';

    /**
     * @return array<string, mixed>
     */
    public function translateForPrint(User $user, StudentProfile $profile, string $locale = self::LOCALE_DE): array
    {
        if ($locale !== self::LOCALE_DE) {
            throw new RuntimeException('Only German (de) CV translation is supported.');
        }

        $presented = CvProfilePresenter::present($user, $profile);
        $sourceFields = $this->extractTranslatableFields($presented);
        $hash = $this->hashFields($sourceFields);

        $cache = is_array($profile->cv_translation_cache) ? $profile->cv_translation_cache : [];
        $localeCache = is_array($cache[$locale] ?? null) ? $cache[$locale] : null;

        if (
            is_array($localeCache)
            && ($localeCache['hash'] ?? null) === $hash
            && is_array($localeCache['fields'] ?? null)
        ) {
            return $this->mergeTranslatedFields($presented, $localeCache['fields']);
        }

        if ($sourceFields === []) {
            return $presented;
        }

        $translatedFields = $this->translateViaDeepL(array_values($sourceFields));
        $fieldMap = array_combine(array_keys($sourceFields), $translatedFields);

        if ($fieldMap === false) {
            throw new RuntimeException('Failed to map DeepL translations to CV fields.');
        }

        $cache[$locale] = [
            'hash' => $hash,
            'translated_at' => now()->toIso8601String(),
            'fields' => $fieldMap,
        ];

        $profile->forceFill([
            'cv_translation_cache' => $cache,
        ])->save();

        return $this->mergeTranslatedFields($presented, $fieldMap);
    }

    public function clearCache(StudentProfile $profile): void
    {
        if ($profile->cv_translation_cache === null) {
            return;
        }

        $profile->forceFill([
            'cv_translation_cache' => null,
        ])->save();
    }

    /**
     * @param  array<string, mixed>  $cv
     * @return array<string, string>
     */
    public function extractTranslatableFields(array $cv): array
    {
        $fields = [];

        $this->addScalar($fields, 'introduction', $cv['introduction'] ?? null);
        $this->addScalar($fields, 'gap_reasons', $cv['gap_reasons'] ?? null);
        $this->addScalar($fields, 'nationality', $cv['nationality'] ?? null);
        $this->addScalar($fields, 'current_address', $cv['current_address'] ?? null);
        $this->addScalar($fields, 'permanent_address', $cv['permanent_address'] ?? null);
        $this->addScalar($fields, 'hobbies', $cv['hobbies'] ?? null);
        $this->addScalar($fields, 'drivers_license', $cv['drivers_license'] ?? null);
        $this->addScalar($fields, 'place_of_birth', $cv['place_of_birth'] ?? null);

        foreach (array_values(is_array($cv['skills'] ?? null) ? $cv['skills'] : []) as $index => $skill) {
            $this->addScalar($fields, "skills.{$index}", $skill);
        }

        foreach (array_values(is_array($cv['languages'] ?? null) ? $cv['languages'] : []) as $index => $language) {
            $this->addScalar($fields, "languages.{$index}", $language);
        }

        $vitae = is_array($cv['vitae'] ?? null) ? $cv['vitae'] : [];

        foreach (array_values(is_array($vitae['experiences'] ?? null) ? $vitae['experiences'] : []) as $index => $job) {
            if (! is_array($job)) {
                continue;
            }

            $this->addScalar($fields, "vitae.experiences.{$index}.organization", $job['organization'] ?? null);
            $this->addScalar($fields, "vitae.experiences.{$index}.department", $job['department'] ?? null);
            $this->addScalar($fields, "vitae.experiences.{$index}.designation", $job['designation'] ?? null);
            $this->addScalar($fields, "vitae.experiences.{$index}.cases_handled", $job['cases_handled'] ?? null);
            $this->addScalar($fields, "vitae.experiences.{$index}.bed_capacity", $job['bed_capacity'] ?? null);
            $this->addScalar($fields, "vitae.experiences.{$index}.departments_offered", $job['departments_offered'] ?? null);
            $this->addScalar($fields, "vitae.experiences.{$index}.accreditations", $job['accreditations'] ?? null);
            $this->addScalar($fields, "vitae.experiences.{$index}.critical_cases", $job['critical_cases'] ?? null);
            $this->addScalar($fields, "vitae.experiences.{$index}.medical_equipment", $job['medical_equipment'] ?? null);
            $this->addScalar($fields, "vitae.experiences.{$index}.city", $job['city'] ?? null);
            $this->addScalar($fields, "vitae.experiences.{$index}.state", $job['state'] ?? null);
        }

        foreach (array_values(is_array($vitae['qualifications'] ?? null) ? $vitae['qualifications'] : []) as $index => $item) {
            if (! is_array($item)) {
                continue;
            }

            $this->addScalar($fields, "vitae.qualifications.{$index}.title", $item['title'] ?? null);
            $this->addScalar($fields, "vitae.qualifications.{$index}.college", $item['college'] ?? null);
            $this->addScalar($fields, "vitae.qualifications.{$index}.university", $item['university'] ?? null);
            $this->addScalar($fields, "vitae.qualifications.{$index}.board", $item['board'] ?? null);
            $this->addScalar($fields, "vitae.qualifications.{$index}.language_of_instruction", $item['language_of_instruction'] ?? null);
            $this->addScalar($fields, "vitae.qualifications.{$index}.city", $item['city'] ?? null);
            $this->addScalar($fields, "vitae.qualifications.{$index}.state", $item['state'] ?? null);
            $this->addScalar($fields, "vitae.qualifications.{$index}.country", $item['country'] ?? null);
        }

        foreach (['seminars', 'presentations', 'publications'] as $activityKey) {
            foreach (array_values(is_array($vitae[$activityKey] ?? null) ? $vitae[$activityKey] : []) as $index => $entry) {
                if (! is_array($entry)) {
                    continue;
                }

                $this->addScalar($fields, "vitae.{$activityKey}.{$index}.topic", $entry['topic'] ?? null);
                $this->addScalar($fields, "vitae.{$activityKey}.{$index}.organization", $entry['organization'] ?? null);
                $this->addScalar($fields, "vitae.{$activityKey}.{$index}.location", $entry['location'] ?? null);
            }
        }

        foreach (array_values(is_array($vitae['trainings'] ?? null) ? $vitae['trainings'] : []) as $index => $entry) {
            if (! is_array($entry)) {
                continue;
            }

            $this->addScalar($fields, "vitae.trainings.{$index}.topic", $entry['topic'] ?? null);
            $this->addScalar($fields, "vitae.trainings.{$index}.organization_location", $entry['organization_location'] ?? null);
        }

        foreach (array_values(is_array($vitae['certifications'] ?? null) ? $vitae['certifications'] : []) as $index => $cert) {
            if (! is_array($cert)) {
                continue;
            }

            $this->addScalar($fields, "vitae.certifications.{$index}.name", $cert['name'] ?? null);
            $this->addScalar($fields, "vitae.certifications.{$index}.description", $cert['description'] ?? null);
        }

        foreach (array_values(is_array($vitae['licenses'] ?? null) ? $vitae['licenses'] : []) as $index => $license) {
            if (! is_array($license)) {
                continue;
            }

            $this->addScalar($fields, "vitae.licenses.{$index}.title", $license['title'] ?? null);
        }

        foreach (array_values(is_array($vitae['bilingual'] ?? null) ? $vitae['bilingual'] : []) as $index => $entry) {
            if (! is_array($entry)) {
                continue;
            }

            $this->addScalar($fields, "vitae.bilingual.{$index}.language", $entry['language'] ?? null);
        }

        return $fields;
    }

    /**
     * @param  array<string, string>  $fields
     */
    public function hashFields(array $fields): string
    {
        ksort($fields);

        return hash('sha256', json_encode($fields, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
    }

    /**
     * @param  array<string, mixed>  $cv
     * @param  array<string, string>  $translatedFields
     * @return array<string, mixed>
     */
    public function mergeTranslatedFields(array $cv, array $translatedFields): array
    {
        foreach ($translatedFields as $path => $value) {
            data_set($cv, $path, $value);
        }

        return $cv;
    }

    /**
     * @param  list<string>  $texts
     * @return list<string>
     */
    private function translateViaDeepL(array $texts): array
    {
        $apiKey = config('services.deepl.key');
        $baseUrl = rtrim((string) config('services.deepl.base_url', 'https://api-free.deepl.com'), '/');

        if (blank($apiKey)) {
            throw new RuntimeException('DeepL API key is not configured.');
        }

        if ($texts === []) {
            return [];
        }

        try {
            $response = Http::asForm()
                ->withHeaders([
                    'Authorization' => 'DeepL-Auth-Key '.$apiKey,
                ])
                ->timeout(60)
                ->acceptJson()
                ->post("{$baseUrl}/v2/translate", [
                    'text' => $texts,
                    'source_lang' => 'EN',
                    'target_lang' => 'DE',
                ])
                ->throw();
        } catch (RequestException $exception) {
            report($exception);

            throw new RuntimeException('DeepL translation request failed.', previous: $exception);
        }

        $translations = $response->json('translations');

        if (! is_array($translations) || count($translations) !== count($texts)) {
            throw new RuntimeException('Unexpected DeepL translation response.');
        }

        $results = [];

        foreach ($translations as $index => $translation) {
            $text = is_array($translation) ? ($translation['text'] ?? null) : null;

            if (! is_string($text)) {
                throw new RuntimeException("Missing DeepL translation for index {$index}.");
            }

            $results[] = $text;
        }

        return $results;
    }

    /**
     * @param  array<string, string>  $fields
     */
    private function addScalar(array &$fields, string $path, mixed $value): void
    {
        if (! is_string($value) && ! is_numeric($value)) {
            return;
        }

        $trimmed = trim((string) $value);

        if ($trimmed === '') {
            return;
        }

        $fields[$path] = $trimmed;
    }
}
