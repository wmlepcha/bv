<?php

namespace App\Filament\Pages;

use App\Filament\Clusters\FrontendEditsCluster;
use App\Filament\Support\ImageUploadField;
use App\Models\SiteSetting;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
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

    protected static ?string $cluster = FrontendEditsCluster::class;

    protected static ?string $navigationLabel = 'Header Settings';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'Header Settings';

    protected static ?string $slug = 'header-settings';

    public function mount(): void
    {
        $this->form->fill([
            'logo_light_path' => $this->storagePathForUpload(
                SiteSetting::getValue('header.logo_light_path')
                    ?? SiteSetting::getValue('header.logo_path', '/storage/images/bv-logo-light.png'),
            ),
            'logo_dark_path' => $this->storagePathForUpload(
                SiteSetting::getValue('header.logo_dark_path')
                    ?? SiteSetting::getValue('header.logo_path', '/storage/images/bv-logo.png'),
            ),
            'partner_label' => SiteSetting::getValue(
                'header.partner_label',
                'Official partner of Government of Sikkim and NSDC International',
            ),
            'show_appearance_toggle' => SiteSetting::getValue(
                'header.show_appearance_toggle',
                '1',
            ) === '1',
        ]);
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $this->saveLogoPath('header.logo_light_path', $data['logo_light_path'] ?? null);
        $this->saveLogoPath('header.logo_dark_path', $data['logo_dark_path'] ?? null);

        SiteSetting::setValue('header.partner_label', $data['partner_label']);
        SiteSetting::setValue(
            'header.show_appearance_toggle',
            ($data['show_appearance_toggle'] ?? false) ? '1' : '0',
        );

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
                ImageUploadField::configure(
                    FileUpload::make('logo_light_path')
                        ->label('Light theme logo')
                        ->image()
                        ->disk('public')
                        ->directory('images')
                        ->maxFiles(1),
                    'Shown when the site is in light mode. If empty, the dark theme logo is used. JPG, PNG, WebP, or GIF. Maximum size: 5 MB.',
                ),
                ImageUploadField::configure(
                    FileUpload::make('logo_dark_path')
                        ->label('Dark theme logo')
                        ->image()
                        ->disk('public')
                        ->directory('images')
                        ->maxFiles(1),
                    'Shown when the site is in dark mode. If empty, the light theme logo is used. JPG, PNG, WebP, or GIF. Maximum size: 5 MB.',
                ),
                Textarea::make('partner_label')
                    ->label('Partner label')
                    ->required()
                    ->rows(3),
                Toggle::make('show_appearance_toggle')
                    ->label('Show theme selector')
                    ->helperText('When enabled, visitors can switch light, dark, or system theme from the site header.')
                    ->default(true),
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
