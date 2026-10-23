<?php

namespace App\Filament\Pages;

use App\Filament\Clusters\FrontendEditsCluster;
use App\Filament\Concerns\AdminOnly;
use App\Models\SiteSetting;
use App\Services\AboutPageService;
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
class ManageAboutPageSettings extends Page
{
    use AdminOnly;

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInformationCircle;

    protected static string|UnitEnum|null $navigationGroup = 'About Page';

    protected static ?string $cluster = FrontendEditsCluster::class;

    protected static ?string $navigationLabel = 'About Page Settings';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'About Page Settings';

    protected static ?string $slug = 'about-page-settings';

    public function mount(): void
    {
        $this->form->fill([
            'hero_title' => SiteSetting::getValue('about.hero.title', 'About Us'),
            'overview_tagline' => SiteSetting::getValue(
                'about.overview.tagline',
                'Bilingual Vitae®, An EdTech and Future Work Company.',
            ),
            'mission' => SiteSetting::getValue(
                'about.mission',
                'To reach the last mile by making local talent professionally visible and globally employable through language proficiency, technology, and trusted professional profiling.',
            ),
            'vision' => SiteSetting::getValue(
                'about.vision',
                "To become the world's most recognized bilingual proficiency profiling and publishing platform.",
            ),
            'leapfrog_title' => SiteSetting::getValue('about.leapfrog.title', 'LEAPFROG'),
            'leapfrog_subtitle' => SiteSetting::getValue(
                'about.leapfrog.subtitle',
                'Our Learning & Leadership Philosophy',
            ),
            'impact_paragraph' => SiteSetting::getValue(
                'about.impact.paragraph',
                'Bilingual Vitae® strives to create measurable impact by improving employability, enhancing communication, strengthening digital competence, and preparing learners for lifelong success in an evolving global workforce market.',
            ),
            'impact_closing' => SiteSetting::getValue(
                'about.impact.closing',
                'We measure our success not merely by certificates awarded, but by careers launched, confidence built, opportunities created, and lives transformed.',
            ),
            'seo_title' => SiteSetting::getValue('about.seo.title', 'About Us'),
            'seo_description' => SiteSetting::getValue(
                'about.seo.description',
                'Bilingual Vitae® is an EdTech and future work company specializing in bilingual proficiency profiling, language training, and global employability — empowering local talent with technology-driven career pathways.',
            ),
        ]);
    }

    public function save(): void
    {
        $data = $this->form->getState();

        SiteSetting::setValue('about.hero.title', $data['hero_title']);
        SiteSetting::setValue('about.overview.tagline', $data['overview_tagline']);
        SiteSetting::setValue('about.mission', $data['mission']);
        SiteSetting::setValue('about.vision', $data['vision']);
        SiteSetting::setValue('about.leapfrog.title', $data['leapfrog_title']);
        SiteSetting::setValue('about.leapfrog.subtitle', $data['leapfrog_subtitle']);
        SiteSetting::setValue('about.impact.paragraph', $data['impact_paragraph']);
        SiteSetting::setValue('about.impact.closing', $data['impact_closing']);
        SiteSetting::setValue('about.seo.title', $data['seo_title']);
        SiteSetting::setValue('about.seo.description', $data['seo_description']);

        AboutPageService::forgetCache();

        Notification::make()
            ->success()
            ->title('About page settings saved')
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
                Tabs::make('AboutPage')
                    ->tabs([
                        Tab::make('Hero')
                            ->schema([
                                TextInput::make('hero_title')
                                    ->label('Page title')
                                    ->required(),
                            ]),
                        Tab::make('Overview')
                            ->schema([
                                TextInput::make('overview_tagline')
                                    ->label('Tagline')
                                    ->required(),
                            ]),
                        Tab::make('Mission & Vision')
                            ->schema([
                                Textarea::make('mission')
                                    ->label('Mission')
                                    ->required()
                                    ->rows(4),
                                Textarea::make('vision')
                                    ->label('Vision')
                                    ->required()
                                    ->rows(3),
                            ]),
                        Tab::make('LEAPFROG')
                            ->schema([
                                TextInput::make('leapfrog_title')
                                    ->label('Title')
                                    ->required(),
                                TextInput::make('leapfrog_subtitle')
                                    ->label('Subtitle')
                                    ->required(),
                            ]),
                        Tab::make('Impact')
                            ->schema([
                                Textarea::make('impact_paragraph')
                                    ->label('Main paragraph')
                                    ->required()
                                    ->rows(4),
                                Textarea::make('impact_closing')
                                    ->label('Closing line')
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
