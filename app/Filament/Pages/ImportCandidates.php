<?php

namespace App\Filament\Pages;

use App\Filament\Clusters\CandidatesCluster;
use App\Models\User;
use App\Services\BulkActivationCodeService;
use App\Services\CandidateImportService;
use App\Services\CandidateImportTemplateService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Html;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;
use UnitEnum;

/**
 * @property-read Schema $form
 */
class ImportCandidates extends Page
{
    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    /**
     * @var array{
     *     created: list<array{email: string, candidate_id: string, temp_password: string}>,
     *     skipped: list<array{row: int, email: string, reason: string}>,
     *     failed: list<array{row: int, email: string|null, errors: string}>
     * }|null
     */
    public ?array $importResult = null;

    /**
     * @var list<array{email: string, candidate_id: string|null, registration_code: string}>|null
     */
    public ?array $activationCodeResult = null;

    protected static ?string $cluster = CandidatesCluster::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentArrowUp;

    protected static string|UnitEnum|null $navigationGroup = null;

    protected static ?string $navigationLabel = 'Import Candidates';

    protected static ?int $navigationSort = 3;

    protected static ?string $title = 'Import Candidates';

    protected static ?string $slug = 'import-candidates';

    public function mount(): void
    {
        $this->form->fill();
    }

    public function defaultForm(Schema $schema): Schema
    {
        return $schema->statePath('data');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Upload spreadsheet')
                    ->description('Template columns (for candidate ID): last_name, first_name, email, state (full name, e.g. Sikkim), special_id (COI, RC, VC, or OTH), registration_year. Students fill phone, DOB, and other CV fields after login. Extra optional columns are still accepted if you add them: mobile_whatsapp (+country code), date_of_birth (Y-m-d), place_of_birth, civil_status, gender.')
                    ->components([
                        FileUpload::make('spreadsheet')
                            ->label('Candidates XLSX')
                            ->acceptedFileTypes([
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                'application/vnd.ms-excel',
                            ])
                            ->disk('local')
                            ->directory('candidate-imports')
                            ->required()
                            ->helperText('Download the template for the correct headers and a sample row. Imported candidates remain unpaid until they redeem a registration code.'),
                    ]),
            ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getFormContentComponent(),
                Html::make(fn (): HtmlString => new HtmlString(
                    view('filament.pages.import-candidates-results', [
                        'importResult' => $this->importResult,
                        'activationCodeResult' => $this->activationCodeResult,
                    ])->render()
                )),
            ]);
    }

    public function getFormContentComponent(): Component
    {
        return Form::make([EmbeddedSchema::make('form')])
            ->id('form')
            ->livewireSubmitHandler('import')
            ->footer([
                Actions::make([
                    Action::make('import')
                        ->label('Import candidates')
                        ->submit('import')
                        ->keyBindings(['mod+s']),
                ]),
            ]);
    }

    public function import(CandidateImportService $importService): void
    {
        $this->activationCodeResult = null;

        $data = $this->form->getState();
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
            $this->importResult = $importService->import($absolutePath);
        } catch (Throwable $exception) {
            Notification::make()
                ->danger()
                ->title('Import failed')
                ->body($exception->getMessage())
                ->send();

            return;
        } finally {
            Storage::disk('local')->delete($path);
            $this->form->fill();
        }

        $created = count($this->importResult['created']);
        $skipped = count($this->importResult['skipped']);
        $failed = count($this->importResult['failed']);

        Notification::make()
            ->success()
            ->title('Import finished')
            ->body("Created {$created}, skipped {$skipped}, failed {$failed}.")
            ->send();
    }

    public function generateActivationCodes(BulkActivationCodeService $service): void
    {
        $userEmails = collect($this->importResult['created'] ?? [])
            ->pluck('email')
            ->filter()
            ->values()
            ->all();

        $userIds = null;

        if ($userEmails !== []) {
            $userIds = User::query()
                ->whereIn('email', $userEmails)
                ->pluck('id')
                ->all();
        }

        $this->activationCodeResult = $service->generateForUnpaidStudents($userIds);

        Notification::make()
            ->success()
            ->title('Registration codes ready')
            ->body(count($this->activationCodeResult).' code(s) generated or reused.')
            ->send();
    }

    public function downloadImportResults(): ?StreamedResponse
    {
        if ($this->importResult === null) {
            return null;
        }

        $filename = 'candidate-import-results-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, ['status', 'email', 'candidate_id', 'temp_password', 'details']);

            foreach ($this->importResult['created'] as $row) {
                fputcsv($handle, ['created', $row['email'], $row['candidate_id'], $row['temp_password'], '']);
            }

            foreach ($this->importResult['skipped'] as $row) {
                fputcsv($handle, ['skipped', $row['email'], '', '', $row['reason']]);
            }

            foreach ($this->importResult['failed'] as $row) {
                fputcsv($handle, ['failed', $row['email'] ?? '', '', '', $row['errors']]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function downloadActivationCodes(): ?StreamedResponse
    {
        if ($this->activationCodeResult === null) {
            return null;
        }

        $filename = 'registration-codes-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, ['email', 'candidate_id', 'registration_code']);

            foreach ($this->activationCodeResult as $row) {
                fputcsv($handle, [$row['email'], $row['candidate_id'] ?? '', $row['registration_code']]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('downloadCandidateImportTemplate')
                ->label('Download candidate import template')
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->action(fn (CandidateImportTemplateService $templates): StreamedResponse => $templates->download()),
            Action::make('downloadImportResults')
                ->label('Download import results')
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->visible(fn (): bool => $this->importResult !== null)
                ->action(fn () => $this->downloadImportResults()),
            Action::make('generateActivationCodes')
                ->label('Generate registration codes')
                ->icon(Heroicon::OutlinedKey)
                ->color('success')
                ->visible(fn (): bool => ($this->importResult['created'] ?? []) !== [])
                ->requiresConfirmation()
                ->modalHeading('Generate registration codes')
                ->modalDescription('Creates BV-26-XXXX codes for the candidates created in the last import. Students must redeem a code to unlock the dashboard.')
                ->action(fn (BulkActivationCodeService $service) => $this->generateActivationCodes($service)),
            Action::make('downloadActivationCodes')
                ->label('Download registration codes')
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->visible(fn (): bool => $this->activationCodeResult !== null)
                ->action(fn () => $this->downloadActivationCodes()),
        ];
    }
}
