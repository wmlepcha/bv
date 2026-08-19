<?php

namespace App\Filament\Pages;

use App\Filament\Clusters\FrontendEditsCluster;
use App\Models\SiteSetting;
use BackedEnum;
use Filament\Actions\Action;
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
class ManageFooterSettings extends Page
{
    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|UnitEnum|null $navigationGroup = 'Footer';

    protected static ?string $cluster = FrontendEditsCluster::class;

    protected static ?string $navigationLabel = 'Footer Settings';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'Footer Settings';

    protected static ?string $slug = 'footer-settings';

    public function mount(): void
    {
        $this->form->fill([
            'contact_email' => SiteSetting::getValue('footer.contact_email', 'hello@bilingualvitae.com'),
            'contact_phone' => SiteSetting::getValue('footer.contact_phone', '+91 8250312500'),
            'copyright' => SiteSetting::getValue('footer.copyright', 'Bilingual Vitae®. All rights reserved.'),
        ]);
    }

    public function save(): void
    {
        $data = $this->form->getState();

        SiteSetting::setValue('footer.contact_email', $data['contact_email']);
        SiteSetting::setValue('footer.contact_phone', $data['contact_phone']);
        SiteSetting::setValue('footer.copyright', $data['copyright']);

        Notification::make()
            ->success()
            ->title('Footer settings saved')
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
                TextInput::make('contact_email')
                    ->label('Contact email')
                    ->email()
                    ->required(),
                TextInput::make('contact_phone')
                    ->label('Contact phone')
                    ->required(),
                Textarea::make('copyright')
                    ->label('Copyright text')
                    ->required()
                    ->rows(2)
                    ->helperText('Year is added automatically on the site.'),
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
