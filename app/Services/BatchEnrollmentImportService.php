<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\Batch;
use App\Models\BatchEnrollment;
use App\Models\User;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use RuntimeException;
use Throwable;

class BatchEnrollmentImportService
{
    /**
     * @var list<string>
     */
    public const REQUIRED_HEADERS = [
        'candidate_id',
    ];

    public function __construct(
        private readonly BatchEnrollmentService $enrollmentService,
    ) {}

    /**
     * @return array{
     *     enrolled: list<array{row: int, candidate_id: string, name: string}>,
     *     skipped: list<array{row: int, candidate_id: string, reason: string}>,
     *     failed: list<array{row: int, candidate_id: string|null, errors: string}>
     * }
     */
    public function import(Batch $batch, string $absolutePath): array
    {
        $spreadsheet = IOFactory::load($absolutePath);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, false);

        if ($rows === []) {
            throw new \InvalidArgumentException('The spreadsheet is empty.');
        }

        $headerRow = array_shift($rows);
        $headers = $this->normalizeHeaders($headerRow);
        $this->assertHeaders($headers);

        $enrolled = [];
        $skipped = [];
        $failed = [];

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;

            if ($this->rowIsEmpty($row)) {
                continue;
            }

            $data = $this->mapRow($headers, $row);
            $candidateId = isset($data['candidate_id']) ? trim((string) $data['candidate_id']) : '';

            if ($candidateId === '') {
                $failed[] = [
                    'row' => $rowNumber,
                    'candidate_id' => null,
                    'errors' => 'Missing candidate_id.',
                ];

                continue;
            }

            $student = User::query()
                ->where('student_number', $candidateId)
                ->first();

            if ($student === null) {
                $failed[] = [
                    'row' => $rowNumber,
                    'candidate_id' => $candidateId,
                    'errors' => 'No candidate found with this candidate ID.',
                ];

                continue;
            }

            if ($student->role !== UserRole::Student) {
                $failed[] = [
                    'row' => $rowNumber,
                    'candidate_id' => $candidateId,
                    'errors' => 'This user is not a candidate.',
                ];

                continue;
            }

            $existing = BatchEnrollment::query()
                ->where('student_id', $student->id)
                ->first();

            if ($existing !== null) {
                if ($existing->batch_id === $batch->id) {
                    $skipped[] = [
                        'row' => $rowNumber,
                        'candidate_id' => $candidateId,
                        'reason' => 'Already enrolled in this batch.',
                    ];

                    continue;
                }

                $failed[] = [
                    'row' => $rowNumber,
                    'candidate_id' => $candidateId,
                    'errors' => 'Already enrolled in another batch. Transfer or remove them first.',
                ];

                continue;
            }

            try {
                $this->enrollmentService->enroll($batch, $student);

                $enrolled[] = [
                    'row' => $rowNumber,
                    'candidate_id' => $candidateId,
                    'name' => $student->name,
                ];
            } catch (RuntimeException|Throwable $exception) {
                $failed[] = [
                    'row' => $rowNumber,
                    'candidate_id' => $candidateId,
                    'errors' => $exception->getMessage(),
                ];
            }
        }

        return compact('enrolled', 'skipped', 'failed');
    }

    /**
     * @param  list<mixed>|null  $headerRow
     * @return list<string>
     */
    private function normalizeHeaders(?array $headerRow): array
    {
        if ($headerRow === null) {
            return [];
        }

        return array_map(
            static fn (mixed $header): string => Str::of((string) $header)->trim()->lower()->toString(),
            $headerRow,
        );
    }

    /**
     * @param  list<string>  $headers
     */
    private function assertHeaders(array $headers): void
    {
        foreach (self::REQUIRED_HEADERS as $required) {
            if (! in_array($required, $headers, true)) {
                throw new \InvalidArgumentException(
                    "Missing required column: {$required}. Expected headers: ".implode(', ', self::REQUIRED_HEADERS)
                );
            }
        }
    }

    /**
     * @param  list<string>  $headers
     * @param  list<mixed>  $row
     * @return array<string, mixed>
     */
    private function mapRow(array $headers, array $row): array
    {
        $data = [];

        foreach ($headers as $index => $header) {
            if ($header === '') {
                continue;
            }

            $value = $row[$index] ?? null;

            if (is_string($value)) {
                $value = trim($value);
            }

            $data[$header] = $value === '' ? null : $value;
        }

        return $data;
    }

    /**
     * @param  list<mixed>  $row
     */
    private function rowIsEmpty(array $row): bool
    {
        foreach ($row as $cell) {
            if ($cell !== null && trim((string) $cell) !== '') {
                return false;
            }
        }

        return true;
    }
}
