<?php

namespace App\Filament\Pages;

use App\Filament\Clusters\FrontendEditsCluster;
use App\Models\SiteSetting;
use App\Services\ServicesPageService;
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
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * @property-read Schema $form
 */
class ManageServicesPageSettings extends Page
{
    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBriefcase;

    protected static string|UnitEnum|null $navigationGroup = 'Services Page';

    protected static ?string $cluster = FrontendEditsCluster::class;

    protected static ?string $navigationLabel = 'Services Page Settings';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'Services Page Settings';

    protected static ?string $slug = 'services-page-settings';

    public function mount(): void
    {
        $this->form->fill([
            'hero_title' => SiteSetting::getValue('services.hero.title', 'Products & Services'),
            'hero_subtitle' => SiteSetting::getValue(
                'services.hero.subtitle',
                'Language training, employability skills, and global placement pathways designed to make local talent globally employable.',
            ),
            'seo_title' => SiteSetting::getValue('services.seo.title', 'Products & Services'),
            'seo_description' => SiteSetting::getValue(
                'services.seo.description',
                'Explore Bilingual Vitae® courses in German, Japanese, French, and Spanish, employability training, expatriate programmes, and placement support in Germany and Austria.',
            ),
        ]);
    }

    public function save(): void
    {
        $data = $this->form->getState();

        SiteSetting::setValue('services.hero.title', $data['hero_title']);
        SiteSetting::setValue('services.hero.subtitle', $data['hero_subtitle']);
        SiteSetting::setValue('services.seo.title', $data['seo_title']);
        SiteSetting::setValue('services.seo.description', $data['seo_description']);

        ServicesPageService::forgetCache();

        Notification::make()
            ->success()
            ->title('Services page settings saved')
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
                Tabs::make('ServicesPage')
                    ->tabs([
                        Tab::make('Hero')
                            ->schema([
                                TextInput::make('hero_title')
                                    ->label('Page title')
                                    ->required(),
                                Textarea::make('hero_subtitle')
                                    ->label('Subtitle')
                                    ->rows(3),
                            ]),
                        Tab::make('SEO')
                            ->schema([
                                TextInput::make('seo_title')
                                    ->label('Page title')
                                    ->required(),
                                Textarea::make('seo_description')
                                    ->label('Meta description')
                                    ->required()
                                    ->rows(3),
                            ]),
                    ]),
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
