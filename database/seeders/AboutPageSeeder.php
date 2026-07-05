<?php

namespace Database\Seeders;

use App\Models\AboutCoreValue;
use App\Models\AboutJourneyMilestone;
use App\Models\AboutParagraph;
use App\Models\SiteSetting;
use Illuminate\Database\Seeder;

class AboutPageSeeder extends Seeder
{
    public function run(): void
    {
        SiteSetting::setValue('about.hero.title', 'About Us');
        SiteSetting::setValue(
            'about.overview.tagline',
            'Bilingual Vitae, An EdTech and Future Work Company.',
        );
        SiteSetting::setValue(
            'about.mission',
            'To reach the last mile by making local talent professionally visible and globally employable through language proficiency, technology, and trusted professional profiling.',
        );
        SiteSetting::setValue(
            'about.vision',
            "To become the world's most recognized bilingual proficiency profiling and publishing platform.",
        );
        SiteSetting::setValue('about.leapfrog.title', 'LEAPFROG');
        SiteSetting::setValue(
            'about.leapfrog.subtitle',
            'Our Learning & Leadership Philosophy',
        );
        SiteSetting::setValue(
            'about.impact.paragraph',
            'Bilingual Vitae strives to create measurable impact by improving employability, enhancing communication, strengthening digital competence, and preparing learners for lifelong success in an evolving global workforce market.',
        );
        SiteSetting::setValue(
            'about.impact.closing',
            'We measure our success not merely by certificates awarded, but by careers launched, confidence built, opportunities created, and lives transformed.',
        );
        SiteSetting::setValue('about.seo.title', 'About Us');
        SiteSetting::setValue(
            'about.seo.description',
            'Bilingual Vitae is an EdTech and future work company specializing in bilingual proficiency profiling, language training, and global employability — empowering local talent with technology-driven career pathways.',
        );

        $overviewParagraphs = [
            'Bilingual Vitae is a professional bilingual proficiency profiling and publishing company, a modern, technology-driven, people-first company.',
            'We specialize in language proficiency training through our digital platform and offline learning centres, helping individuals develop the language and employability skills needed to access international career opportunities. Beyond training, our proprietary proficiency profiling and publishing methodology enhances candidates\' professional visibility, credibility, and global employability.',
            'By combining language education, technology, and digital identity, we empower local talent to gain worldwide recognition and connect with global opportunities.',
        ];

        foreach ($overviewParagraphs as $index => $body) {
            AboutParagraph::query()->updateOrCreate(
                ['section' => AboutParagraph::SECTION_OVERVIEW, 'sort_order' => $index],
                ['body' => $body, 'is_visible' => true],
            );
        }

        $leapfrogParagraphs = [
            'At Bilingual Vitae, innovation is the foundation of how we learn, lead, and create impact.',
            'Our LEAPFROG Philosophy reflects our belief that learners, institutions, and leaders can accelerate their growth by embracing innovation, collaboration, and technology. By connecting people across cultures, removing barriers, and promoting inclusion, we create opportunities that enable individuals and organizations to thrive in a global environment.',
            'At the heart of our brand is the LEAPFROG philosophy — This philosophy shapes every programme, technology, partnership, and solution we build.',
        ];

        foreach ($leapfrogParagraphs as $index => $body) {
            AboutParagraph::query()->updateOrCreate(
                ['section' => AboutParagraph::SECTION_LEAPFROG, 'sort_order' => $index],
                ['body' => $body, 'is_visible' => true],
            );
        }

        $coreValuesIntro = [
            'At Bilingual Vitae, we believe in the transformative power of language and technology. We are dedicated to inspiring language learning aspirants, preparing them for a future of endless opportunities, and empowering both individuals and institutions to embark on successful linguistic journeys.',
            'We extend our commitment to the Recruiting Companies and Institutions (Private/Government), recognizing that language proficiency transcends boundaries to further solving friction with Bilingual Vitae\'s technology and facilitating global partnerships, and fostering collaboration.',
        ];

        foreach ($coreValuesIntro as $index => $body) {
            AboutParagraph::query()->updateOrCreate(
                ['section' => AboutParagraph::SECTION_CORE_VALUES_INTRO, 'sort_order' => $index],
                ['body' => $body, 'is_visible' => true],
            );
        }

        $coreValues = [
            ['letter' => 'L', 'title' => 'Leverage', 'description' => "We maximize language training's effectiveness through innovation. We embrace technology, creativity, and modern learning methodologies to maximize success.", 'sort_order' => 0],
            ['letter' => 'E', 'title' => 'Empower', 'description' => 'Empowering learners and organizations (recruiters, institutions and government agencies) is our dual focus.', 'sort_order' => 1],
            ['letter' => 'A', 'title' => 'Adapt', 'description' => 'We stay current by evolving with changing language and global job market dynamics.', 'sort_order' => 2],
            ['letter' => 'P', 'title' => 'Progress', 'description' => 'Measurable progress in job placements, fluency, and cultural adaptability is our goal. We operate with transparency, professionalism, and accountability in everything we do (Integrity).', 'sort_order' => 3],
            ['letter' => 'F', 'title' => 'Foster', 'description' => 'We foster a collaborative and culturally aware environment.', 'sort_order' => 4],
            ['letter' => 'R', 'title' => 'Reflect', 'description' => 'Reflection is essential for continuous improvement.', 'sort_order' => 5],
            ['letter' => 'O', 'title' => 'Optimize', 'description' => 'Our products and services are ever evolving through optimization.', 'sort_order' => 6],
            ['letter' => 'G', 'title' => 'Grow', 'description' => 'Our commitment is to help individuals and organizations grow globally. We provide accessible and equitable opportunities for people from all backgrounds (Inclusivity). We build strong partnerships with recruiting companies, educational institutions, government agencies, and communities (Collaboration).', 'sort_order' => 7],
        ];

        foreach ($coreValues as $value) {
            AboutCoreValue::query()->updateOrCreate(
                ['letter' => $value['letter']],
                [...$value, 'is_visible' => true],
            );
        }

        $milestones = [
            [
                'slug' => 'established',
                'period' => 'ESTABLISHED',
                'title' => '11 September 2020',
                'layout' => AboutJourneyMilestone::LAYOUT_CENTER,
                'image_path' => null,
                'image_alt' => null,
                'bullets' => [],
                'sort_order' => 0,
            ],
            [
                'slug' => '2021-2023',
                'period' => '2021 – 2023',
                'title' => 'Ed-tech recognition & early growth',
                'layout' => AboutJourneyMilestone::LAYOUT_TEXT_LEFT,
                'image_path' => 'https://images.unsplash.com/photo-1523240795612-9a054b0db644?w=800&q=80',
                'image_alt' => 'Ed-tech training and startup growth',
                'bullets' => [
                    'Recognized as Ed-tech & Future Work Startup by DPIIT, Government of India.',
                    'Raised 1.5 million (INR) in Pre-Seed round — Funded by SISF, Start Up India.',
                    'Trained 200+ students — Japanese Language, German Language, French Language and Spanish Language.',
                ],
                'sort_order' => 1,
            ],
            [
                'slug' => '2024-2025',
                'period' => '2024 – 2025',
                'title' => 'Association with the Government of Sikkim',
                'layout' => AboutJourneyMilestone::LAYOUT_TEXT_RIGHT,
                'image_path' => 'https://images.unsplash.com/photo-1576091160399-112ba8d25d1d?w=800&q=80',
                'image_alt' => 'Healthcare training and nurse placement programme',
                'bullets' => [
                    'Phase 1 — Project contract secured from the Government of Sikkim under Sikkim Inspires, a Government of Sikkim and World Bank Partnership Program — German language training and recruitment programme. Designed and Executed by Bilingual Vitae and its consortium partners in Germany.',
                    '20 Indian Registered Nurses successfully trained. Successfully Placed and Successfully integrated in the German Healthcare Institutions as a Registered Nursing Professional.',
                    '300+ Indian Nurses and other sector candidates onboarded and Undergoing German Language and employability skill training for the Nurses Recruitment and Ausbildung program for Germany and Austria.',
                ],
                'sort_order' => 2,
            ],
            [
                'slug' => '2026',
                'period' => '2026',
                'title' => 'NSDC International partnership',
                'layout' => AboutJourneyMilestone::LAYOUT_TEXT_LEFT,
                'image_path' => 'https://images.unsplash.com/photo-1521737711862-ea3e973327c1?w=800&q=80',
                'image_alt' => 'Global career opportunities and international placement',
                'bullets' => [
                    'Association with National Skill Development Corporation International, a Public-Private Partnership (PPP) operating under the aegis of the Government of India\'s Ministry of Skill Development & Entrepreneurship (MSDE).',
                    '450+ Indian candidates from pan-India onboarded and undergoing German Language and employability skill training for Nurses Recruitment and Ausbildung program for Germany and Austria (Latest Update).',
                    '25+ National German Visa secured (Latest Update).',
                    '200+ candidates placed, received job contracts (Latest Update).',
                    'Highest package achieved — 40,988.28 euro per annum (Latest Update).',
                ],
                'sort_order' => 3,
            ],
        ];

        foreach ($milestones as $milestone) {
            AboutJourneyMilestone::query()->updateOrCreate(
                ['slug' => $milestone['slug']],
                [...$milestone, 'is_visible' => true],
            );
        }
    }
}
