<?php

namespace Database\Seeders;

use App\Models\HomeFeatureItem;
use App\Models\HomeHighlight;
use App\Models\HomeProgramCard;
use App\Models\HomeServiceCard;
use App\Models\HomeStat;
use App\Models\HomeTestimonial;
use App\Models\SiteSetting;
use Illuminate\Database\Seeder;

class HomePageSeeder extends Seeder
{
    public function run(): void
    {
        SiteSetting::setValue('home.hero.badge', 'Global workforce readiness');
        SiteSetting::setValue('home.hero.headline', 'People flex their linguistic & professional skills here…');
        SiteSetting::setValue(
            'home.hero.subtitle',
            'Helping people become workplace-ready with strong communication and professional skills.',
        );
        SiteSetting::setValue(
            'home.hero.card_text',
            'With us language becomes your bridge, not a barrier.',
        );
        SiteSetting::setValue('home.hero.software_suite_heading', 'Software suite');
        SiteSetting::setValue('home.hero.services_heading', 'Services');

        SiteSetting::setValue('home.crafting.badge', 'Candidate development');
        SiteSetting::setValue('home.crafting.heading', 'Crafting people. Linguistically & Professionally.');
        SiteSetting::setValue(
            'home.crafting.body',
            'At Bilingual Vitae®, we blend linguistic and professional skills with deep human values while making candidates ready to be a part of the ever-demanding global workforce market.',
        );
        SiteSetting::setValue('home.crafting.image_path', 'images/students-of-bilingual-vitae.jpg');
        SiteSetting::setValue('home.crafting.image_alt', 'Bilingual Vitae® students in training');

        SiteSetting::setValue('home.recruiters.badge', 'For recruiters & employers');
        SiteSetting::setValue('home.recruiters.heading', 'Recruiters never leave unsatisfied.');
        SiteSetting::setValue(
            'home.recruiters.body',
            'At Bilingual Vitae®, recruiters and employers get linguistically and professionally well-prepared candidates who are ready to perform from day one.',
        );
        SiteSetting::setValue('home.recruiters.image_path', 'images/recruiters-of-bilingual-vitae.jpg');
        SiteSetting::setValue('home.recruiters.image_alt', 'Recruiters partnering with Bilingual Vitae®');

        SiteSetting::setValue(
            'home.services.heading',
            'Whether you\'re a student, professional or a recruiter, we\'ve got you covered.',
        );
        SiteSetting::setValue('home.services.subtitle', 'You didn\'t come this far to stop');

        SiteSetting::setValue('home.seo.title', 'Language Training & Career Support in Sikkim');
        SiteSetting::setValue(
            'home.seo.description',
            'Bilingual Vitae® helps people in Sikkim become workplace-ready through language training, employability skills, placement support, and migration guidance.',
        );

        $stats = [
            ['value' => 400, 'suffix' => ' +', 'label' => 'candidates onboarded and undergoing training', 'sort_order' => 0],
            ['value' => 50, 'suffix' => ' +', 'label' => 'candidates certified', 'sort_order' => 1],
            ['value' => 50, 'suffix' => ' +', 'label' => 'successful International placement', 'sort_order' => 2],
            ['value' => 21, 'suffix' => '+', 'label' => 'successful visa', 'sort_order' => 3],
            ['value' => 15, 'suffix' => '+', 'label' => 'successful Integration', 'sort_order' => 4],
        ];

        foreach ($stats as $stat) {
            HomeStat::query()->updateOrCreate(
                ['label' => $stat['label']],
                [...$stat, 'is_visible' => true],
            );
        }

        HomeHighlight::query()->delete();

        $highlights = [
            [
                'label' => 'Digital products for candidates',
                'group' => HomeHighlight::GROUP_SOFTWARE_SUITE,
                'sort_order' => 0,
            ],
            [
                'label' => 'Software suite – for Recruiters',
                'group' => HomeHighlight::GROUP_SOFTWARE_SUITE,
                'sort_order' => 1,
            ],
            [
                'label' => 'Software suite – for Government Agencies',
                'group' => HomeHighlight::GROUP_SOFTWARE_SUITE,
                'sort_order' => 2,
            ],
            [
                'label' => 'Language & Employability Skill Training',
                'group' => HomeHighlight::GROUP_SERVICES,
                'sort_order' => 0,
            ],
            [
                'label' => 'Placement I Migration I Integration support',
                'group' => HomeHighlight::GROUP_SERVICES,
                'sort_order' => 1,
            ],
        ];

        foreach ($highlights as $highlight) {
            HomeHighlight::query()->create([
                ...$highlight,
                'is_visible' => true,
            ]);
        }

        $craftingPillars = [
            ['section' => HomeFeatureItem::SECTION_CRAFTING, 'label' => 'Linguistic fluency', 'sort_order' => 0],
            ['section' => HomeFeatureItem::SECTION_CRAFTING, 'label' => 'Professional etiquette', 'sort_order' => 1],
            ['section' => HomeFeatureItem::SECTION_CRAFTING, 'label' => 'Workplace confidence', 'sort_order' => 2],
            ['section' => HomeFeatureItem::SECTION_CRAFTING, 'label' => 'Global career readiness', 'sort_order' => 3],
        ];

        foreach ($craftingPillars as $pillar) {
            HomeFeatureItem::query()->updateOrCreate(
                ['section' => $pillar['section'], 'label' => $pillar['label']],
                [...$pillar, 'is_visible' => true],
            );
        }

        $recruiterBenefits = [
            ['section' => HomeFeatureItem::SECTION_RECRUITERS, 'label' => 'Interview-ready communication', 'sort_order' => 0],
            ['section' => HomeFeatureItem::SECTION_RECRUITERS, 'label' => 'Industry-aligned skill sets', 'sort_order' => 1],
            ['section' => HomeFeatureItem::SECTION_RECRUITERS, 'label' => 'Faster shortlisting cycles', 'sort_order' => 2],
            ['section' => HomeFeatureItem::SECTION_RECRUITERS, 'label' => 'Higher placement success rates', 'sort_order' => 3],
        ];

        foreach ($recruiterBenefits as $benefit) {
            HomeFeatureItem::query()->updateOrCreate(
                ['section' => $benefit['section'], 'label' => $benefit['label']],
                [...$benefit, 'is_visible' => true],
            );
        }

        HomeTestimonial::query()->updateOrCreate(
            ['section' => HomeTestimonial::SECTION_CRAFTING, 'author' => 'Sarah Mitchell'],
            [
                'quote' => 'Exceptional training that transformed our hiring pipeline.',
                'role' => 'HR Director',
                'sort_order' => 0,
                'is_visible' => true,
            ],
        );

        HomeTestimonial::query()->updateOrCreate(
            ['section' => HomeTestimonial::SECTION_RECRUITERS, 'author' => 'James Chen'],
            [
                'quote' => 'The quality of candidates exceeded our expectations.',
                'role' => 'Talent Acquisition Lead',
                'sort_order' => 0,
                'is_visible' => true,
            ],
        );

        $serviceCards = [
            [
                'title' => 'Language & Employability Training Programs',
                'href' => '/services/language-training',
                'image_path' => 'https://images.unsplash.com/photo-1434030216411-0b793f4b4173?w=600&q=80',
                'sort_order' => 0,
            ],
            [
                'title' => 'Placement & Recruitment Support Services',
                'href' => '/services/placement',
                'image_path' => 'https://images.unsplash.com/photo-1454165804606-c3d57bc86b40?w=600&q=80',
                'sort_order' => 1,
            ],
            [
                'title' => 'Migration & Integration Support Services',
                'href' => '/services/migration',
                'image_path' => 'https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?w=600&q=80',
                'sort_order' => 2,
            ],
        ];

        foreach ($serviceCards as $card) {
            HomeServiceCard::query()->updateOrCreate(
                ['title' => $card['title']],
                [...$card, 'is_visible' => true],
            );
        }

        SiteSetting::setValue('home.programs.badge', 'Our Programs');
        SiteSetting::setValue('home.programs.heading', 'Boost your career with our expertise');
        SiteSetting::setValue('home.programs.cta_label', 'View All Programs');
        SiteSetting::setValue('home.programs.cta_href', '/services');

        $programCards = [
            [
                'title' => 'Language & Employability Training Programs',
                'description' => 'Build workplace-ready language and employability skills through structured training tailored for global opportunities.',
                'href' => '/services#courses',
                'image_path' => 'https://images.unsplash.com/photo-1455390582262-044cdead277a?w=800&q=80',
                'sort_order' => 0,
            ],
            [
                'title' => 'Placement | Migration | Integration Support Services',
                'description' => 'Get end-to-end support for placement, migration, and integration so you can move confidently into the next chapter.',
                'href' => '/services#placement-integration',
                'image_path' => 'https://images.unsplash.com/photo-1454165804606-c3d57bc86b40?w=800&q=80',
                'sort_order' => 1,
            ],
        ];

        foreach ($programCards as $card) {
            HomeProgramCard::query()->updateOrCreate(
                ['title' => $card['title']],
                [...$card, 'is_visible' => true],
            );
        }
    }
}
