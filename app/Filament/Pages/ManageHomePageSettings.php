<?php

namespace App\Filament\Pages;

use App\Models\SiteSetting;
use App\Services\HomePageService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
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
class ManageHomePageSettings extends Page
{
    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHome;

    protected static string|UnitEnum|null $navigationGroup = 'Homepage';

    protected static ?string $navigationLabel = 'Homepage Settings';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'Homepage Settings';

    protected static ?string $slug = 'homepage-settings';

    public function mount(): void
    {
        $this->form->fill([
            'hero_badge' => SiteSetting::getValue('home.hero.badge', 'Global workforce readiness'),
            'hero_headline' => SiteSetting::getValue('home.hero.headline', 'People flex their linguistic & professional skills here…'),
            'hero_subtitle' => SiteSetting::getValue(
                'home.hero.subtitle',
                'Helping people become workplace-ready with strong communication and professional skills.',
            ),
            'hero_card_text' => SiteSetting::getValue(
                'home.hero.card_text',
                'Bridging language excellence with professional growth for international careers.',
            ),
            'crafting_badge' => SiteSetting::getValue('home.crafting.badge', 'Candidate development'),
            'crafting_heading' => SiteSetting::getValue('home.crafting.heading', 'Crafting people. Linguistically & Professionally.'),
            'crafting_body' => SiteSetting::getValue(
                'home.crafting.body',
                'At Bilingual Vitae, we blend linguistic and professional skills with deep human values while making candidates ready to be a part of the ever-demanding global workforce market.',
            ),
            'crafting_image_path' => $this->normalizeImagePath(SiteSetting::getValue('home.crafting.image_path', 'images/students-of-bilingual-vitae.jpg')),
            'crafting_image_alt' => SiteSetting::getValue('home.crafting.image_alt', 'Bilingual Vitae students in training'),
            'recruiters_badge' => SiteSetting::getValue('home.recruiters.badge', 'For recruiters & employers'),
            'recruiters_heading' => SiteSetting::getValue('home.recruiters.heading', 'Recruiters never leave unsatisfied.'),
            'recruiters_body' => SiteSetting::getValue(
                'home.recruiters.body',
                'At Bilingual Vitae, recruiters and employers get linguistically and professionally well-prepared candidates who are ready to perform from day one.',
            ),
            'recruiters_image_path' => $this->normalizeImagePath(SiteSetting::getValue('home.recruiters.image_path', 'images/recruiters-of-bilingual-vitae.jpg')),
            'recruiters_image_alt' => SiteSetting::getValue('home.recruiters.image_alt', 'Recruiters partnering with Bilingual Vitae'),
            'services_heading' => SiteSetting::getValue(
                'home.services.heading',
                'Whether you\'re a student, professional or a recruiter, we\'ve got you covered.',
            ),
            'services_subtitle' => SiteSetting::getValue('home.services.subtitle', 'You didn\'t come this far to stop'),
            'seo_title' => SiteSetting::getValue('home.seo.title', 'Language Training & Career Support in Sikkim'),
            'seo_description' => SiteSetting::getValue(
                'home.seo.description',
                'Bilingual Vitae helps people in Sikkim become workplace-ready through language training, employability skills, placement support, and migration guidance.',
            ),
        ]);
    }

    public function save(): void
    {
        $data = $this->form->getState();

        SiteSetting::setValue('home.hero.badge', $data['hero_badge']);
        SiteSetting::setValue('home.hero.headline', $data['hero_headline']);
        SiteSetting::setValue('home.hero.subtitle', $data['hero_subtitle']);
        SiteSetting::setValue('home.hero.card_text', $data['hero_card_text']);

        SiteSetting::setValue('home.crafting.badge', $data['crafting_badge']);
        SiteSetting::setValue('home.crafting.heading', $data['crafting_heading']);
        SiteSetting::setValue('home.crafting.body', $data['crafting_body']);
        SiteSetting::setValue('home.crafting.image_path', $this->storeImagePath($data['crafting_image_path']));
        SiteSetting::setValue('home.crafting.image_alt', $data['crafting_image_alt']);

        SiteSetting::setValue('home.recruiters.badge', $data['recruiters_badge']);
        SiteSetting::setValue('home.recruiters.heading', $data['recruiters_heading']);
        SiteSetting::setValue('home.recruiters.body', $data['recruiters_body']);
        SiteSetting::setValue('home.recruiters.image_path', $this->storeImagePath($data['recruiters_image_path']));
        SiteSetting::setValue('home.recruiters.image_alt', $data['recruiters_image_alt']);

        SiteSetting::setValue('home.services.heading', $data['services_heading']);
        SiteSetting::setValue('home.services.subtitle', $data['services_subtitle']);

        SiteSetting::setValue('home.seo.title', $data['seo_title']);
        SiteSetting::setValue('home.seo.description', $data['seo_description']);

        HomePageService::forgetCache();

        Notification::make()
            ->success()
            ->title('Homepage settings saved')
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
                Tabs::make('Homepage')
                    ->tabs([
                        Tab::make('Hero')
                            ->schema([
                                TextInput::make('hero_badge')
                                    ->label('Badge')
                                    ->required(),
                                TextInput::make('hero_headline')
                                    ->label('Headline')
                                    ->required(),
                                Textarea::make('hero_subtitle')
                                    ->label('Subtitle')
                                    ->required()
                                    ->rows(3),
                                Textarea::make('hero_card_text')
                                    ->label('Side card text')
                                    ->required()
                                    ->rows(3),
                            ]),
                        Tab::make('Crafting')
                            ->schema([
                                TextInput::make('crafting_badge')
                                    ->label('Badge')
                                    ->required(),
                                TextInput::make('crafting_heading')
                                    ->label('Heading')
                                    ->required(),
                                Textarea::make('crafting_body')
                                    ->label('Body')
                                    ->required()
                                    ->rows(4),
                                FileUpload::make('crafting_image_path')
                                    ->label('Image')
                                    ->image()
                                    ->disk('public')
                                    ->directory('images')
                                    ->maxFiles(1),
                                TextInput::make('crafting_image_alt')
                                    ->label('Image alt text')
                                    ->required(),
                            ]),
                        Tab::make('Recruiters')
                            ->schema([
                                TextInput::make('recruiters_badge')
                                    ->label('Badge')
                                    ->required(),
                                TextInput::make('recruiters_heading')
                                    ->label('Heading')
                                    ->required(),
                                Textarea::make('recruiters_body')
                                    ->label('Body')
                                    ->required()
                                    ->rows(4),
                                FileUpload::make('recruiters_image_path')
                                    ->label('Image')
                                    ->image()
                                    ->disk('public')
                                    ->directory('images')
                                    ->maxFiles(1),
                                TextInput::make('recruiters_image_alt')
                                    ->label('Image alt text')
                                    ->required(),
                            ]),
                        Tab::make('Services')
                            ->schema([
                                Textarea::make('services_heading')
                                    ->label('Heading')
                                    ->required()
                                    ->rows(2),
                                TextInput::make('services_subtitle')
                                    ->label('Subtitle')
                                    ->required(),
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

    protected function normalizeImagePath(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        return str_starts_with($path, '/storage/')
            ? str_replace('/storage/', '', $path)
            : $path;
    }

    /**
     * @param  array<int, string>|string|null  $path
     */
    protected function storeImagePath(array|string|null $path): string
    {
        if (is_array($path)) {
            $path = $path[0] ?? '';
        }

        if (blank($path)) {
            return '';
        }

        return str_starts_with($path, '/storage/') ? $path : $path;
    }
}
