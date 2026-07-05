<?php

namespace Database\Seeders;

use App\Models\ServiceCategory;
use App\Models\ServiceGroup;
use App\Models\ServiceItem;
use App\Models\SiteSetting;
use Illuminate\Database\Seeder;

class ServicesPageSeeder extends Seeder
{
    public function run(): void
    {
        SiteSetting::setValue('services.hero.title', 'Products & Services');
        SiteSetting::setValue(
            'services.hero.subtitle',
            'Language training, employability skills, and global placement pathways designed to make local talent globally employable.',
        );
        SiteSetting::setValue('services.seo.title', 'Products & Services');
        SiteSetting::setValue(
            'services.seo.description',
            'Explore Bilingual Vitae courses in German, Japanese, French, and Spanish, employability training, expatriate programmes, and placement support in Germany and Austria.',
        );

        $courses = ServiceCategory::query()->updateOrCreate(
            ['slug' => 'courses'],
            ['title' => 'Courses', 'sort_order' => 0, 'is_visible' => true],
        );

        $placement = ServiceCategory::query()->updateOrCreate(
            ['slug' => 'placement-integration'],
            ['title' => 'Placement & Integration', 'sort_order' => 1, 'is_visible' => true],
        );

        $language = ServiceGroup::query()->updateOrCreate(
            ['service_category_id' => $courses->id, 'slug' => 'language'],
            ['title' => 'Language', 'sort_order' => 0, 'is_visible' => true],
        );

        $employability = ServiceGroup::query()->updateOrCreate(
            ['service_category_id' => $courses->id, 'slug' => 'employability-skills'],
            ['title' => 'Employability Skills', 'sort_order' => 1, 'is_visible' => true],
        );

        $mobility = ServiceGroup::query()->updateOrCreate(
            ['service_category_id' => $courses->id, 'slug' => 'international-mobility'],
            ['title' => 'International Mobility', 'sort_order' => 2, 'is_visible' => true],
        );

        $registeredNurse = ServiceGroup::query()->updateOrCreate(
            ['service_category_id' => $placement->id, 'slug' => 'registered-nurse'],
            ['title' => 'Registered Nurse', 'sort_order' => 0, 'is_visible' => true],
        );

        $ausbildung = ServiceGroup::query()->updateOrCreate(
            ['service_category_id' => $placement->id, 'slug' => 'ausbildung'],
            ['title' => 'Ausbildung (On Job Training)', 'sort_order' => 1, 'is_visible' => true],
        );

        $physiotherapist = ServiceGroup::query()->updateOrCreate(
            ['service_category_id' => $placement->id, 'slug' => 'registered-physiotherapist'],
            ['title' => 'Registered Physiotherapist', 'sort_order' => 2, 'is_visible' => true],
        );

        $this->seedGermanLanguageItems($language);
        $this->seedPlaceholderLanguageItems($language);
        $this->seedEmployabilityItem($employability);
        $this->seedMobilityItem($mobility);
        $this->seedRegisteredNurseItems($registeredNurse);
        $this->seedAusbildungItems($ausbildung);
        $this->seedPhysiotherapistItem($physiotherapist);
    }

    protected function seedGermanLanguageItems(ServiceGroup $group): void
    {
        $items = [
            [
                'slug' => 'german-offline',
                'title' => 'German Language — Offline Classes',
                'family_label' => 'German',
                'family_description' => 'Choose the learning format that fits your goals and schedule. All programs are designed to help you build strong communication skills in German.',
                'delivery_modes' => ['offline'],
                'level_range' => 'A1 Level - B2 Level',
                'hours' => '1080 - 1200',
                'duration_months' => '08 - 10',
                'centres' => [
                    'Gangtok, Sikkim',
                    'Dagapur, Salbari, Siliguri, West Bengal',
                    'Salugara, Siliguri, West Bengal',
                ],
                'intake_periods' => ['January', 'April', 'July', 'October'],
                'sort_order' => 0,
            ],
            [
                'slug' => 'german-online-live',
                'title' => 'German Language — Online Live Classes',
                'family_label' => 'German',
                'delivery_modes' => ['online_live'],
                'level_range' => 'A1 Level - B2 Level',
                'hours' => '1080 - 1200',
                'duration_months' => '10 - 12',
                'intake_periods' => ['January', 'April', 'July', 'October'],
                'sort_order' => 1,
            ],
            [
                'slug' => 'german-hybrid',
                'title' => 'German Language — Hybrid Classes',
                'family_label' => 'German',
                'delivery_modes' => ['hybrid'],
                'level_range' => 'A1 - B2 Level',
                'hours' => '1080 - 1200',
                'duration_months' => '10 - 12',
                'notes' => 'A1 - A2 Online Live Classes | B1 - B2 Offline Classes',
                'intake_periods' => ['January', 'April', 'July', 'October'],
                'sort_order' => 2,
            ],
        ];

        foreach ($items as $item) {
            ServiceItem::query()->updateOrCreate(
                ['service_group_id' => $group->id, 'slug' => $item['slug']],
                array_merge($item, [
                    'kind' => ServiceItem::KIND_COURSE,
                    'is_visible' => true,
                ]),
            );
        }
    }

    protected function seedPlaceholderLanguageItems(ServiceGroup $group): void
    {
        $languages = [
            ['slug' => 'japanese', 'title' => 'Japanese', 'sort_order' => 3],
            ['slug' => 'french', 'title' => 'French', 'sort_order' => 4],
            ['slug' => 'spanish', 'title' => 'Spanish', 'sort_order' => 5],
        ];

        foreach ($languages as $language) {
            ServiceItem::query()->updateOrCreate(
                ['service_group_id' => $group->id, 'slug' => $language['slug']],
                [
                    'title' => $language['title'],
                    'family_label' => $language['title'],
                    'kind' => ServiceItem::KIND_COURSE,
                    'sort_order' => $language['sort_order'],
                    'is_visible' => true,
                ],
            );
        }
    }

    protected function seedEmployabilityItem(ServiceGroup $group): void
    {
        ServiceItem::query()->updateOrCreate(
            ['service_group_id' => $group->id, 'slug' => 'communication-digital-literacy'],
            [
                'title' => 'Communication & Digital Literacy',
                'kind' => ServiceItem::KIND_COURSE,
                'delivery_modes' => ['offline', 'online_live'],
                'hours' => '60 - 180',
                'duration_months' => '01 - 03',
                'sort_order' => 0,
                'is_visible' => true,
            ],
        );
    }

    protected function seedMobilityItem(ServiceGroup $group): void
    {
        ServiceItem::query()->updateOrCreate(
            ['service_group_id' => $group->id, 'slug' => 'expatriate-training'],
            [
                'title' => 'Expatriate Training',
                'kind' => ServiceItem::KIND_COURSE,
                'delivery_modes' => ['offline', 'online_live'],
                'hours' => '60 - 180',
                'duration_months' => '01 - 03',
                'sort_order' => 0,
                'is_visible' => true,
            ],
        );
    }

    protected function seedRegisteredNurseItems(ServiceGroup $group): void
    {
        $items = [
            [
                'slug' => 'germany',
                'title' => 'Germany',
                'venues' => ['Hospitals', 'Clinics', 'Nursing Homes'],
                'timeline' => '12 Months (approx.) after B2 Certification',
                'certificates' => ['Goethe', 'telc', 'OSD'],
                'sort_order' => 0,
            ],
            [
                'slug' => 'austria',
                'title' => 'Austria',
                'venues' => ['Hospitals', 'Clinics', 'Nursing Homes'],
                'timeline' => '12 Months (approx.) after B2 Certification',
                'certificates' => ['Goethe', 'telc', 'OSD'],
                'sort_order' => 1,
            ],
        ];

        foreach ($items as $item) {
            ServiceItem::query()->updateOrCreate(
                ['service_group_id' => $group->id, 'slug' => $item['slug']],
                array_merge($item, [
                    'kind' => ServiceItem::KIND_PLACEMENT,
                    'is_visible' => true,
                ]),
            );
        }
    }

    protected function seedAusbildungItems(ServiceGroup $group): void
    {
        $items = [
            [
                'slug' => 'germany',
                'title' => 'Germany',
                'sectors' => ['Nursing', 'Food Production', 'Retail Sales', 'Mechatronics', 'Hospitality'],
                'certificates' => ['Goethe', 'telc', 'OSD'],
                'sort_order' => 0,
            ],
            [
                'slug' => 'austria',
                'title' => 'Austria',
                'sectors' => ['Nursing', 'Food Production', 'Retail Sales', 'Mechatronics', 'Hospitality'],
                'certificates' => ['Goethe', 'telc', 'OSD'],
                'sort_order' => 1,
            ],
        ];

        foreach ($items as $item) {
            ServiceItem::query()->updateOrCreate(
                ['service_group_id' => $group->id, 'slug' => $item['slug']],
                array_merge($item, [
                    'kind' => ServiceItem::KIND_PLACEMENT,
                    'is_visible' => true,
                ]),
            );
        }
    }

    protected function seedPhysiotherapistItem(ServiceGroup $group): void
    {
        ServiceItem::query()->updateOrCreate(
            ['service_group_id' => $group->id, 'slug' => 'germany'],
            [
                'title' => 'Germany',
                'kind' => ServiceItem::KIND_PLACEMENT,
                'venues' => ['Hospitals', 'Clinics', 'Nursing Homes', 'Specialized Rehabilitation Centers'],
                'timeline' => '12 Months (approx.) after B2 Certification',
                'certificates' => ['Goethe', 'telc', 'OSD'],
                'sort_order' => 0,
                'is_visible' => true,
            ],
        );
    }
}
