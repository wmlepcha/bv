<?php

namespace App\Filament\Pages;

use App\Filament\Clusters\PaymentsCluster;
use App\Filament\Concerns\AdminOnly;
use App\Models\SiteSetting;
use App\Support\RegistrationTerms;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\RichEditor;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Str;
use UnitEnum;

/**
 * @property-read Schema $form
 */
class ManagePaymentSettings extends Page
{
    use AdminOnly;

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    protected static ?string $cluster = PaymentsCluster::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static string|UnitEnum|null $navigationGroup = null;

    protected static ?string $navigationLabel = 'Payment Settings';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'Payment Settings';

    protected static ?string $slug = 'payment-settings';

    public function mount(): void
    {
        $this->form->fill([
            'registration_terms_html' => RegistrationTerms::html(),
        ]);
    }

    public function save(): void
    {
        $data = $this->form->getState();

        SiteSetting::setValue(
            RegistrationTerms::SITE_SETTING_KEY,
            Str::sanitizeHtml((string) ($data['registration_terms_html'] ?? '')),
        );

        Notification::make()
            ->success()
            ->title('Payment settings saved')
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
                RichEditor::make('registration_terms_html')
                    ->label('Terms and conditions')
                    ->toolbarButtons([
                        ['bold', 'italic', 'underline', 'link'],
                        ['h2', 'h3'],
                        ['bulletList', 'orderedList'],
                    ])
                    ->fileAttachments(false)
                    ->columnSpanFull()
                    ->helperText('Shown before students choose a plan or redeem a registration code.'),
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
}
