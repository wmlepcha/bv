<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\AdminOnly;
use App\Models\SiteSetting;
use App\Services\ProjectsPageService;
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
class ManageProjectsPageSettings extends Page
{
    use AdminOnly;

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedNewspaper;

    protected static string|UnitEnum|null $navigationGroup = 'Projects / Blog';

    protected static ?string $navigationLabel = 'Projects Page Settings';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'Projects Page Settings';

    protected static ?string $slug = 'projects-page-settings';

    public function mount(): void
    {
        $this->form->fill([
            'hero_title' => SiteSetting::getValue('projects.hero.title', 'Projects'),
            'hero_subtitle' => SiteSetting::getValue(
                'projects.hero.subtitle',
                'Insights on language learning, multilingualism, and global employability from the Bilingual Vitae® editorial team.',
            ),
            'seo_title' => SiteSetting::getValue('projects.seo.title', 'Projects'),
            'seo_description' => SiteSetting::getValue(
                'projects.seo.description',
                'Discover Bilingual Vitae® projects and initiatives advancing language education, employability, and workforce development across Sikkim and beyond.',
            ),
        ]);
    }

    public function save(): void
    {
        $data = $this->form->getState();

        SiteSetting::setValue('projects.hero.title', $data['hero_title']);
        SiteSetting::setValue('projects.hero.subtitle', $data['hero_subtitle']);
        SiteSetting::setValue('projects.seo.title', $data['seo_title']);
        SiteSetting::setValue('projects.seo.description', $data['seo_description']);

        ProjectsPageService::forgetCache();

        Notification::make()
            ->success()
            ->title('Projects page settings saved')
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
                Tabs::make('ProjectsPage')
                    ->tabs([
                        Tab::make('Hero')
                            ->schema([
                                TextInput::make('hero_title')
                                    ->label('Page title')
                                    ->required(),
                                Textarea::make('hero_subtitle')
                                    ->label('Subtitle')
                                    ->required()
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
