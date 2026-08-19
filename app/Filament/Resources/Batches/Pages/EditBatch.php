<?php

namespace App\Filament\Resources\Batches\Pages;

use App\Filament\Resources\Batches\BatchResource;
use App\Models\Batch;
use App\Services\BatchEnrollmentImportService;
use App\Services\BatchEnrollmentTemplateService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Components\Html;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

/**
 * @property-read Batch $record
 */
class EditBatch extends EditRecord
{
    protected static string $resource = BatchResource::class;

    /**
     * @var array{
     *     enrolled: list<array{row: int, candidate_id: string, name: string}>,
     *     skipped: list<array{row: int, candidate_id: string, reason: string}>,
     *     failed: list<array{row: int, candidate_id: string|null, errors: string}>
     * }|null
     */
    public ?array $importResult = null;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('downloadEnrollmentTemplate')
                ->label('Download enrollment template')
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->action(fn (BatchEnrollmentTemplateService $templates): StreamedResponse => $templates->download()),
            Action::make('importEnrollments')
                ->label('Import enrollments')
                ->icon(Heroicon::OutlinedDocumentArrowUp)
                ->modalHeading('Import enrollments')
                ->modalDescription('Upload an XLSX with a candidate_id column (name, email, and mobile_whatsapp are optional helper columns). Delete rows you do not want in this batch, then upload. Download the template for a list of unenrolled candidates.')
                ->form([
                    FileUpload::make('spreadsheet')
                        ->label('Enrollments XLSX')
                        ->acceptedFileTypes([
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            'application/vnd.ms-excel',
                        ])
                        ->disk('local')
                        ->directory('batch-enrollment-imports')
                        ->required()
                        ->helperText('Required header: candidate_id. Optional: name, email, mobile_whatsapp (ignored on import).'),
                ])
                ->action(function (array $data, BatchEnrollmentImportService $importService): void {
                    $path = $data['spreadsheet'] ?? null;

                    if (is_array($path)) {
                        $path = $path[0] ?? null;
                    }

                    if (! is_string($path) || $path === '') {
                        Notification::make()
                            ->danger()
                            ->title('Please upload an XLSX file.')
                            ->send();

                        return;
                    }

                    $absolutePath = Storage::disk('local')->path($path);

                    try {
                        /** @var Batch $batch */
                        $batch = $this->getRecord();
                        $this->importResult = $importService->import($batch, $absolutePath);
                    } catch (Throwable $exception) {
                        Notification::make()
                            ->danger()
                            ->title('Import failed')
                            ->body($exception->getMessage())
                            ->send();

                        return;
                    } finally {
                        Storage::disk('local')->delete($path);
                    }

                    $enrolled = count($this->importResult['enrolled']);
                    $skipped = count($this->importResult['skipped']);
                    $failed = count($this->importResult['failed']);

                    Notification::make()
                        ->success()
                        ->title('Enrollment import finished')
                        ->body("Enrolled {$enrolled}, skipped {$skipped}, failed {$failed}.")
                        ->send();
                }),
            DeleteAction::make(),
        ];
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getFormContentComponent(),
                Html::make(fn (): HtmlString => new HtmlString(
                    view('filament.resources.batches.enrollment-import-results', [
                        'importResult' => $this->importResult,
                    ])->render()
                )),
                $this->getRelationManagersContentComponent(),
            ]);
    }
}
