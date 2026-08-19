<?php

namespace App\Support;

/**
 * Default shape and helpers for student_profiles.vitae_data.
 *
 * @phpstan-type Experience array{
 *     duration: string|null,
 *     start_date: string|null,
 *     end_date: string|null,
 *     designation: string|null,
 *     department: string|null,
 *     organization: string|null,
 *     city: string|null,
 *     state: string|null,
 *     cases_handled: string|null,
 *     bed_capacity: string|null,
 *     medical_equipment: string|null,
 *     departments_offered: string|null,
 *     accreditations: string|null,
 *     critical_cases: string|null,
 *     has_experience_letter: bool,
 *     certificate_path: string|null
 * }
 * @phpstan-type Qualification array{
 *     start_date: string|null,
 *     end_date: string|null,
 *     title: string|null,
 *     college: string|null,
 *     university: string|null,
 *     city: string|null,
 *     state: string|null,
 *     country: string|null,
 *     board: string|null,
 *     language_of_instruction: string|null
 * }
 * @phpstan-type Training array{
 *     date: string|null,
 *     topic: string|null,
 *     organization_location: string|null,
 *     certificate_path: string|null
 * }
 * @phpstan-type ActivityEntry array{
 *     date: string|null,
 *     topic: string|null,
 *     organization: string|null,
 *     location: string|null,
 *     evidence_type: string|null,
 *     certificate_path: string|null,
 *     link_url: string|null
 * }
 * @phpstan-type Certification array{
 *     name: string|null,
 *     description: string|null,
 *     certificate_path: string|null
 * }
 * @phpstan-type License array{
 *     title: string|null,
 *     registration_number: string|null,
 *     issued_date: string|null,
 *     valid_till: string|null,
 *     certificate_path: string|null
 * }
 * @phpstan-type BilingualEntry array{
 *     language: string|null,
 *     language_type: string|null,
 *     proficiency: string|null,
 *     certification_level: string|null,
 *     certificates: string|null,
 *     certificate_path: string|null
 * }
 * @phpstan-type VitaeData array{
 *     is_newbie: bool,
 *     experiences: list<Experience>,
 *     qualifications: list<Qualification>,
 *     trainings: list<Training>,
 *     seminars: list<ActivityEntry>,
 *     presentations: list<ActivityEntry>,
 *     publications: list<ActivityEntry>,
 *     certifications: list<Certification>,
 *     licenses: list<License>,
 *     bilingual: list<BilingualEntry>
 * }
 */
class VitaeData
{
    /**
     * @return VitaeData
     */
    public static function empty(): array
    {
        return [
            'is_newbie' => false,
            'experiences' => [],
            'qualifications' => [],
            'trainings' => [],
            'seminars' => [],
            'presentations' => [],
            'publications' => [],
            'certifications' => [],
            'licenses' => [],
            'bilingual' => [],
        ];
    }

    /**
     * @param  array<string, mixed>|null  $data
     * @return VitaeData
     */
    public static function normalize(?array $data): array
    {
        if ($data === null) {
            return self::empty();
        }

        return [
            'is_newbie' => (bool) ($data['is_newbie'] ?? false),
            'experiences' => self::normalizeList($data['experiences'] ?? [], self::emptyExperience()),
            'qualifications' => self::normalizeList($data['qualifications'] ?? [], self::emptyQualification()),
            'trainings' => self::normalizeList($data['trainings'] ?? [], self::emptyTraining()),
            'seminars' => self::normalizeList($data['seminars'] ?? [], self::emptyActivityEntry()),
            'presentations' => self::normalizeList($data['presentations'] ?? [], self::emptyActivityEntry()),
            'publications' => self::normalizeList($data['publications'] ?? [], self::emptyActivityEntry()),
            'certifications' => self::normalizeList($data['certifications'] ?? [], self::emptyCertification()),
            'licenses' => self::normalizeList($data['licenses'] ?? [], self::emptyLicense()),
            'bilingual' => self::normalizeBilingual($data['bilingual'] ?? null),
        ];
    }

    /**
     * @return BilingualEntry
     */
    public static function emptyBilingualEntry(): array
    {
        return [
            'language' => null,
            'language_type' => null,
            'proficiency' => null,
            'certification_level' => null,
            'certificates' => null,
            'certificate_path' => null,
        ];
    }

    /**
     * @return list<BilingualEntry>
     */
    private static function normalizeBilingual(mixed $bilingual): array
    {
        if (! is_array($bilingual)) {
            return [];
        }

        // Legacy shape: { first_language, second_language, ... }
        if (array_key_exists('first_language', $bilingual) || array_key_exists('second_language', $bilingual)) {
            $entries = [];

            if (filled($bilingual['first_language'] ?? null) || filled($bilingual['first_language_certificates'] ?? null)) {
                $entries[] = array_merge(self::emptyBilingualEntry(), [
                    'language' => $bilingual['first_language'] ?? null,
                    'certificates' => $bilingual['first_language_certificates'] ?? null,
                ]);
            }

            if (filled($bilingual['second_language'] ?? null) || filled($bilingual['second_language_certificates'] ?? null)) {
                $entries[] = array_merge(self::emptyBilingualEntry(), [
                    'language' => $bilingual['second_language'] ?? null,
                    'certificates' => $bilingual['second_language_certificates'] ?? null,
                ]);
            }

            return $entries;
        }

        return self::normalizeList($bilingual, self::emptyBilingualEntry());
    }

    /**
     * @return Experience
     */
    public static function emptyExperience(): array
    {
        return [
            'duration' => null,
            'start_date' => null,
            'end_date' => null,
            'designation' => null,
            'department' => null,
            'organization' => null,
            'city' => null,
            'state' => null,
            'cases_handled' => null,
            'bed_capacity' => null,
            'medical_equipment' => null,
            'departments_offered' => null,
            'accreditations' => null,
            'critical_cases' => null,
            'has_experience_letter' => true,
            'certificate_path' => null,
        ];
    }

    /**
     * @return Qualification
     */
    public static function emptyQualification(): array
    {
        return [
            'start_date' => null,
            'end_date' => null,
            'title' => null,
            'college' => null,
            'university' => null,
            'city' => null,
            'state' => null,
            'country' => null,
            'board' => null,
            'language_of_instruction' => null,
        ];
    }

    /**
     * @return Training
     */
    public static function emptyTraining(): array
    {
        return [
            'date' => null,
            'topic' => null,
            'organization_location' => null,
            'certificate_path' => null,
        ];
    }

    /**
     * @return ActivityEntry
     */
    public static function emptyActivityEntry(): array
    {
        return [
            'date' => null,
            'topic' => null,
            'organization' => null,
            'location' => null,
            'evidence_type' => null,
            'certificate_path' => null,
            'link_url' => null,
        ];
    }

    /**
     * @return Certification
     */
    public static function emptyCertification(): array
    {
        return [
            'name' => null,
            'description' => null,
            'certificate_path' => null,
        ];
    }

    /**
     * @return License
     */
    public static function emptyLicense(): array
    {
        return [
            'title' => null,
            'registration_number' => null,
            'issued_date' => null,
            'valid_till' => null,
            'certificate_path' => null,
        ];
    }

    /**
     * @param  array<string, mixed>  $empty
     * @return list<array<string, mixed>>
     */
    private static function normalizeList(mixed $items, array $empty): array
    {
        if (! is_array($items)) {
            return [];
        }

        $normalized = [];

        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $normalized[] = array_merge($empty, $item);
        }

        return $normalized;
    }
}
