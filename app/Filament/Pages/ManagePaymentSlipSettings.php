<?php

namespace App\Filament\Pages;

use App\Filament\Clusters\PaymentsCluster;
use App\Filament\Concerns\AdminOnly;
use App\Filament\Support\ImageUploadField;
use App\Models\OfficeLocation;
use App\Models\SiteSetting;
use App\Support\Brand;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * @property-read Schema $form
 */
class ManagePaymentSlipSettings extends Page
{
    use AdminOnly;

    public ?array $data = [];

    protected static ?string $cluster = PaymentsCluster::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|UnitEnum|null $navigationGroup = null;

    protected static ?string $navigationLabel = 'Payment Slip';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'Payment Slip Settings';

    protected static ?string $slug = 'payment-slip-settings';

    public function mount(): void
    {
        $this->form->fill([
            'logo_path' => $this->storagePathForUpload(
                SiteSetting::getValue('payment_slip.logo_path')
                    ?? Brand::marketingLogoUrl(),
            ),
            'signature_image_path' => $this->storagePathForUpload(
                SiteSetting::getValue('payment_slip.signature_image_path'),
            ),
            'stamp_image_path' => $this->storagePathForUpload(
                SiteSetting::getValue('payment_slip.stamp_image_path'),
            ),
            'authorized_by' => SiteSetting::getValue('payment_slip.authorized_by', 'AIS - Bilingual Vitae Pvt. Ltd.'),
            'company_address' => SiteSetting::getValue(
                'payment_slip.company_address',
                $this->defaultCompanyAddress(),
            ),
            'company_phone' => SiteSetting::getValue(
                'payment_slip.company_phone',
                SiteSetting::getValue('footer.contact_phone', '+91 8250312500'),
            ),
            'company_email' => SiteSetting::getValue(
                'payment_slip.company_email',
                SiteSetting::getValue('footer.contact_email', 'hello@bilingualvitae.com'),
            ),
            'gstin' => SiteSetting::getValue('payment_slip.gstin'),
            'digital_footer_html' => SiteSetting::getValue(
                'payment_slip.digital_footer_html',
                '<p>This is a digital generated bill. No signature required.</p>',
            ),
        ]);
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $this->saveLogoPath('payment_slip.logo_path', $data['logo_path'] ?? null);
        $this->saveLogoPath('payment_slip.signature_image_path', $data['signature_image_path'] ?? null);
        $this->saveLogoPath('payment_slip.stamp_image_path', $data['stamp_image_path'] ?? null);

        SiteSetting::setValue('payment_slip.authorized_by', (string) ($data['authorized_by'] ?? ''));
        SiteSetting::setValue('payment_slip.company_address', (string) ($data['company_address'] ?? ''));
        SiteSetting::setValue('payment_slip.company_phone', (string) ($data['company_phone'] ?? ''));
        SiteSetting::setValue('payment_slip.company_email', (string) ($data['company_email'] ?? ''));
        SiteSetting::setValue('payment_slip.gstin', (string) ($data['gstin'] ?? ''));
        SiteSetting::setValue(
            'payment_slip.digital_footer_html',
            (string) ($data['digital_footer_html'] ?? ''),
        );

        Notification::make()
            ->success()
            ->title('Payment slip settings saved')
            ->send();
    }

    public function defaultForm(Schema $schema): Schema
    {
        return $schema
            ->statePath('data');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->paymentSlipImageUpload(
                    FileUpload::make('logo_path')->label('Logo'),
                    'Shown at the top of the payment slip.',
                ),
                Textarea::make('company_address')
                    ->label('Company address')
                    ->rows(3)
                    ->required(),
                TextInput::make('company_phone')
                    ->label('Phone number')
                    ->required(),
                TextInput::make('company_email')
                    ->label('Company email')
                    ->email()
                    ->required(),
                TextInput::make('gstin')
                    ->label('GSTIN')
                    ->helperText('Leave empty to hide GSTIN on the payment slip.'),
                TextInput::make('authorized_by')
                    ->label('Authorized by')
                    ->required(),
                $this->paymentSlipImageUpload(
                    FileUpload::make('stamp_image_path')->label('Stamp'),
                    'Shown below the authorized-by name on the payment slip.',
                ),
                $this->paymentSlipImageUpload(
                    FileUpload::make('signature_image_path')->label('Signature'),
                    'Shown next to the stamp, below the authorized-by name.',
                ),
                RichEditor::make('digital_footer_html')
                    ->label('Digital footer message (bottom of slip)')
                    ->fileAttachments(false)
                    ->columnSpanFull(),
            ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getFormContentComponent(),
            ]);
    }

    public function getFormContentComponent(): Component
    {
        return Form::make([EmbeddedSchema::make('form')])
            ->id('form')
            ->livewireSubmitHandler('save')
            ->footer([
                Actions::make([
                    Action::make('save')
                        ->label('Save')
                        ->submit('save')
                        ->keyBindings(['mod+s']),
                ]),
            ]);
    }

    protected function defaultCompanyAddress(): string
    {
        return (string) (OfficeLocation::query()
            ->where('is_visible', true)
            ->orderBy('sort_order')
            ->value('address') ?? '');
    }

    protected function paymentSlipImageUpload(FileUpload $field, string $helperText): FileUpload
    {
        return ImageUploadField::configure(
            $field
                ->image()
                ->disk('public')
                ->visibility('public')
                ->directory('payment-slips')
                ->maxFiles(1)
                ->imagePreviewHeight('120')
                ->getUploadedFileUsing(function (FileUpload $component, string $file): ?array {
                    $storage = $component->getDisk();

                    try {
                        if (! $storage->exists($file)) {
                            return null;
                        }
                    } catch (\Throwable) {
                        return null;
                    }

                    return [
                        'name' => basename($file),
                        'size' => $storage->size($file),
                        'type' => $storage->mimeType($file) ?: 'image/png',
                        'url' => '/storage/'.$file,
                    ];
                }),
            $helperText,
        );
    }

    protected function storagePathForUpload(?string $path): ?string
    {
        if (! filled($path)) {
            return null;
        }

        return str_starts_with($path, '/storage/')
            ? str_replace('/storage/', '', $path)
            : $path;
    }

    protected function saveLogoPath(string $key, mixed $value): void
    {
        $logoPath = is_array($value)
            ? ($value[0] ?? null)
            : $value;

        if (! filled($logoPath)) {
            return;
        }

        SiteSetting::setValue(
            $key,
            str_starts_with($logoPath, '/storage/') ? $logoPath : '/storage/'.$logoPath,
        );
    }
}
