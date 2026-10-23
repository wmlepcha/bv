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

    /**
     * Second sample row: an older student who already paid (payment columns after the required ones).
     *
     * @var list<string|int>
     */
    public const PAID_SAMPLE_ROW = [
        'Lepcha',
        'Dolma',
        'dolma@example.com',
        'Sikkim',
        'COI',
        '2024',
        '2025-06-15',
        25000,
        'cash',
        24,
        '',
        'RCPT-1042',
    ];

    public function download(): StreamedResponse
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Candidates');

        $headers = [...CandidateImportService::REQUIRED_HEADERS, ...CandidateImportService::PAYMENT_HEADERS];

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

        foreach (self::PAID_SAMPLE_ROW as $columnIndex => $value) {
            $header = $headers[$columnIndex] ?? '';
            $coordinate = [$columnIndex + 1, 3];

            if (is_string($value) || $header === 'registration_year') {
                $sheet->setCellValueExplicit($coordinate, (string) $value, DataType::TYPE_STRING);
            } else {
                $sheet->setCellValue($coordinate, $value);
            }
        }

        foreach (range(1, count($headers)) as $columnIndex) {
            $sheet->getColumnDimensionByColumn($columnIndex)->setAutoSize(true);
        }

        $notes = $spreadsheet->createSheet();
        $notes->setTitle('Read me');

        foreach ([
            'Row 2 is a new candidate who has not paid yet: leave the payment columns empty. They stay unpaid until they redeem a registration code.',
            'Row 3 is an older student who already paid: fill paid_on (YYYY-MM-DD), amount (rupees) and payment_method. Their payment is recorded and their dashboard unlocks from the day you import.',
            'payment_method: cash, upi, bank_transfer, cheque, online (earlier website payment) or unknown. duration_months is optional (default 24). access_until (YYYY-MM-DD, in the future) is optional.',
            'A student who is already in the system is matched by email. With payment columns filled they are mapped as paid without any email; without them they are skipped. Students who already have active access are skipped.',
        ] as $line => $text) {
            $notes->setCellValue([1, $line + 1], $text);
        }

        $notes->getColumnDimensionByColumn(1)->setWidth(140);
        $spreadsheet->setActiveSheetIndex(0);

        return response()->streamDownload(function () use ($spreadsheet): void {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, 'candidate-import-template.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
