<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CandidateImportTemplateService
{
    /**
     * Sample values matching REQUIRED_HEADERS order (ID fields only).
     *
     * @var list<string>
     */
    public const SAMPLE_ROW = [
        'Thapa',
        'Nanuka',
        'nanuka@example.com',
        'Sikkim',
        'COI',
        '2024',
    ];

    public function download(): StreamedResponse
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Candidates');

        $headers = CandidateImportService::REQUIRED_HEADERS;

        foreach ($headers as $columnIndex => $header) {
            $sheet->setCellValue([$columnIndex + 1, 1], $header);
        }

        $stringColumns = [
            'email',
            'special_id',
            'registration_year',
        ];

        foreach (self::SAMPLE_ROW as $columnIndex => $value) {
            $header = $headers[$columnIndex] ?? '';
            $coordinate = [$columnIndex + 1, 2];

            if (in_array($header, $stringColumns, true)) {
                $sheet->setCellValueExplicit($coordinate, (string) $value, DataType::TYPE_STRING);
            } else {
                $sheet->setCellValue($coordinate, $value);
            }
        }

        foreach (range(1, count($headers)) as $columnIndex) {
            $sheet->getColumnDimensionByColumn($columnIndex)->setAutoSize(true);
        }

        return response()->streamDownload(function () use ($spreadsheet): void {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, 'candidate-import-template.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
