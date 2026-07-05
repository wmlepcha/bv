<?php

namespace App\Filament\Pages;

use App\Models\SiteSetting;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
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
class ManageHeaderSettings extends Page
{
    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAdjustmentsHorizontal;

    protected static string|UnitEnum|null $navigationGroup = 'Header';

    protected static ?string $navigationLabel = 'Header Settings';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'Header Settings';

    protected static ?string $slug = 'header-settings';

    public function mount(): void
    {
        $logoPath = SiteSetting::getValue('header.logo_path', '/storage/images/bv-logo.png');
        $logoPath = str_starts_with($logoPath, '/storage/')
            ? str_replace('/storage/', '', $logoPath)
            : $logoPath;

        $this->form->fill([
            'logo_path' => $logoPath,
            'partner_label' => SiteSetting::getValue(
                'header.partner_label',
                'Official partner of Government of Sikkim and NSDC International',
            ),
        ]);
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $logoPath = is_array($data['logo_path'] ?? null)
            ? ($data['logo_path'][0] ?? null)
            : ($data['logo_path'] ?? null);

        if (filled($logoPath)) {
            SiteSetting::setValue(
                'header.logo_path',
                str_starts_with($logoPath, '/storage/') ? $logoPath : '/storage/'.$logoPath,
            );
        }

        SiteSetting::setValue('header.partner_label', $data['partner_label']);

        Notification::make()
            ->success()
            ->title('Header settings saved')
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
                FileUpload::make('logo_path')
                    ->label('Site logo')
                    ->image()
                    ->disk('public')
                    ->directory('images')
                    ->maxFiles(1),
                Textarea::make('partner_label')
                    ->label('Partner label')
                    ->required()
                    ->rows(3),
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
