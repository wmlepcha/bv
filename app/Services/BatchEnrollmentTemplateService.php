<?php

namespace App\Services;

use App\Models\User;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BatchEnrollmentTemplateService
{
    public function __construct(
        private readonly BatchEnrollmentService $enrollmentService,
    ) {}

    public function download(): StreamedResponse
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Enrollments');

        $sheet->setCellValue('A1', 'candidate_id');
        $sheet->setCellValue('B1', 'name');
        $sheet->setCellValue('C1', 'email');
        $sheet->setCellValue('D1', 'mobile_whatsapp');

        $students = $this->enrollmentService
            ->unenrolledStudentsQuery()
            ->with('studentProfile:id,user_id,mobile_whatsapp')
            ->whereNotNull('student_number')
            ->where('student_number', '!=', '')
            ->orderBy('name')
            ->get(['id', 'student_number', 'name', 'email']);

        $row = 2;

        foreach ($students as $student) {
            /** @var User $student */
            $sheet->setCellValueExplicit('A'.$row, (string) $student->student_number, DataType::TYPE_STRING);
            $sheet->setCellValue('B'.$row, $student->name);
            $sheet->setCellValue('C'.$row, $student->email);
            $sheet->setCellValueExplicit(
                'D'.$row,
                (string) ($student->studentProfile?->mobile_whatsapp ?? ''),
                DataType::TYPE_STRING,
            );
            $row++;
        }

        // If nobody is available yet, leave a blank data row under the headers
        // so the file still shows the expected shape.
        if ($students->isEmpty()) {
            $sheet->setCellValue('A2', '');
            $sheet->setCellValue('B2', '');
            $sheet->setCellValue('C2', '');
            $sheet->setCellValue('D2', '');
        }

        foreach (['A', 'B', 'C', 'D'] as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        return response()->streamDownload(function () use ($spreadsheet): void {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, 'batch-enrollment-template.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
