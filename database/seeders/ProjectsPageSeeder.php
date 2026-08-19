<?php

namespace Database\Seeders;

use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\BlogPostSection;
use App\Models\SiteSetting;
use App\Services\ProjectsPageService;
use Illuminate\Database\Seeder;

class ProjectsPageSeeder extends Seeder
{
    public function run(): void
    {
        SiteSetting::setValue('projects.hero.title', 'Projects');
        SiteSetting::setValue(
            'projects.hero.subtitle',
            'Insights on language learning, multilingualism, and global employability from the Bilingual Vitae® editorial team.',
        );
        SiteSetting::setValue('projects.seo.title', 'Projects');
        SiteSetting::setValue(
            'projects.seo.description',
            'Explore articles on language learning, bilingual brain science, pronunciation, and multilingual journeys from Bilingual Vitae®.',
        );

        $categories = collect([
            ['name' => 'Cognition', 'slug' => 'cognition', 'sort_order' => 0],
            ['name' => 'Japanese', 'slug' => 'japanese', 'sort_order' => 1],
            ['name' => 'Pronunciation', 'slug' => 'pronunciation', 'sort_order' => 2],
            ['name' => 'Personal', 'slug' => 'personal', 'sort_order' => 3],
            ['name' => 'Strategy', 'slug' => 'strategy', 'sort_order' => 4],
            ['name' => 'Science', 'slug' => 'science', 'sort_order' => 5],
        ])->mapWithKeys(fn (array $data): array => [
            $data['slug'] => BlogCategory::query()->updateOrCreate(
                ['slug' => $data['slug']],
                $data,
            ),
        ]);

        $author = [
            'author_name' => 'Bilingual Vitae® Editorial',
            'author_bio' => 'The Bilingual Vitae® editorial team shares practical guidance on language learning, employability, and multilingual life.',
        ];

        $posts = [
            [
                'slug' => 'how-to-think-in-a-foreign-language',
                'title' => 'How to Think in a Foreign Language',
                'excerpt' => 'Stop translating in your head and start processing meaning directly in your target language with these proven cognitive techniques.',
                'category' => 'cognition',
                'card_layout' => BlogPost::CARD_LAYOUT_WIDE,
                'reading_time_minutes' => 8,
                'sort_order' => 0,
                'featured_image_path' => 'https://images.unsplash.com/photo-1456513080510-7bf3a84b82f8?w=1200&q=80',
                'featured_image_alt' => 'Open notebook for language study',
                'seo_title' => 'How to Think in a Foreign Language | Bilingual Vitae®',
                'seo_description' => 'Learn practical techniques to think directly in a foreign language without mental translation.',
                'seo_keywords' => 'think in foreign language, language learning, bilingual cognition',
                'sections' => [
                    ['type' => BlogPostSection::TYPE_PARAGRAPH, 'content' => ['text' => 'One of the biggest milestones in language acquisition is the moment you stop translating every sentence in your head. Thinking in a foreign language is not a talent reserved for polyglots — it is a trainable skill built through deliberate exposure and habit.']],
                    ['type' => BlogPostSection::TYPE_PARAGRAPH, 'content' => ['text' => 'When learners rely on their native language as an intermediary, fluency feels slow and fragile. The goal is to build direct associations between concepts and target-language words, so comprehension and production become automatic.']],
                    ['type' => BlogPostSection::TYPE_HEADING, 'content' => ['level' => 2, 'text' => 'Techniques that accelerate inner monologue']],
                    ['type' => BlogPostSection::TYPE_BULLET_LIST, 'content' => ['items' => [
                        'Narrate your daily routine silently in the target language.',
                        'Replace single-word lookups with image-based flashcards.',
                        'Read graded content slightly above your comfort level.',
                        'Shadow short audio clips to internalize rhythm and phrasing.',
                        'Keep a one-language-only journal, even if entries are short.',
                    ]]],
                    ['type' => BlogPostSection::TYPE_IMAGE, 'content' => [
                        'path' => 'https://images.unsplash.com/photo-1434030216411-0b793f4b4173?w=1000&q=80',
                        'alt' => 'Student writing in a language notebook',
                        'caption' => 'Daily micro-writing builds direct language associations.',
                        'placement' => 'inline',
                    ]],
                    ['type' => BlogPostSection::TYPE_FAQ, 'content' => ['items' => [
                        ['question' => 'How long until I can think in another language?', 'answer' => 'Most committed learners notice a shift after 3–6 months of daily immersion-style practice, depending on intensity and prior language experience.'],
                        ['question' => 'Should I avoid my native language entirely?', 'answer' => 'Total avoidance is not necessary. The key is increasing the proportion of time you spend processing the target language directly.'],
                    ]]],
                ],
            ],
            [
                'slug' => 'five-best-apps-for-learning-kanji',
                'title' => '5 Best Apps for Learning Kanji',
                'excerpt' => 'A practical roundup of digital tools that make Japanese character study structured, memorable, and sustainable.',
                'category' => 'japanese',
                'card_layout' => BlogPost::CARD_LAYOUT_TALL,
                'reading_time_minutes' => 6,
                'sort_order' => 1,
                'featured_image_path' => 'https://images.unsplash.com/photo-1528164346305-ee4f781d3f76?w=1200&q=80',
                'featured_image_alt' => 'Japanese calligraphy brush',
                'seo_title' => '5 Best Apps for Learning Kanji',
                'seo_description' => 'Compare the top apps for learning Japanese kanji with spaced repetition, stroke order, and reading practice.',
                'seo_keywords' => 'kanji apps, learn Japanese, JLPT study',
                'sections' => [
                    ['type' => BlogPostSection::TYPE_HEADING, 'content' => ['level' => 2, 'text' => 'What makes a kanji app effective?']],
                    ['type' => BlogPostSection::TYPE_BULLET_LIST, 'content' => ['items' => [
                        'WaniKani — radical-based mnemonics with spaced repetition.',
                        'Anki + shared decks — fully customizable review scheduling.',
                        'Kanji Study — excellent stroke order and writing drills.',
                        'Duolingo Japanese — approachable entry point for beginners.',
                        'Bunpro — grammar context that reinforces kanji in sentences.',
                    ]]],
                    ['type' => BlogPostSection::TYPE_IMAGE, 'content' => [
                        'path' => 'https://images.unsplash.com/photo-1542051841857-5f90071e7989?w=1000&q=80',
                        'alt' => 'Tokyo street signs with kanji',
                        'placement' => 'below',
                    ]],
                    ['type' => BlogPostSection::TYPE_PARAGRAPH, 'content' => ['text' => 'The best app is the one you will open every day. Combine digital review with handwritten practice and real-world reading to move characters from recognition to production.']],
                ],
            ],
            [
                'slug' => 'the-secret-to-a-perfect-accent',
                'title' => 'The Secret to a Perfect Accent',
                'excerpt' => 'Accent improvement is less about talent and more about listening precision, muscle memory, and fearless repetition.',
                'category' => 'pronunciation',
                'card_layout' => BlogPost::CARD_LAYOUT_TALL,
                'reading_time_minutes' => 7,
                'sort_order' => 2,
                'featured_image_path' => 'https://images.unsplash.com/photo-1478737270239-2f02b77fc618?w=1200&q=80',
                'featured_image_alt' => 'Microphone for pronunciation practice',
                'seo_title' => 'The Secret to a Perfect Accent',
                'seo_description' => 'Discover how listening drills, shadowing, and phonetic awareness can transform your pronunciation.',
                'seo_keywords' => 'accent training, pronunciation, language speaking skills',
                'sections' => [
                    ['type' => BlogPostSection::TYPE_PARAGRAPH, 'content' => ['text' => 'A natural accent emerges when your ear can hear fine phonetic differences and your mouth can reproduce them without conscious effort. That loop — listen, imitate, adjust — is the core of pronunciation training.']],
                    ['type' => BlogPostSection::TYPE_PARAGRAPH, 'content' => ['text' => 'Record yourself weekly and compare against native speakers. Most learners discover they are closer than they think, but small vowel length and stress errors keep them sounding foreign.']],
                    ['type' => BlogPostSection::TYPE_VIDEO, 'content' => [
                        'source' => 'youtube',
                        'url' => 'https://www.youtube.com/watch?v=d0yGdNEWdn0',
                        'caption' => 'Example shadowing technique for pronunciation practice.',
                    ]],
                    ['type' => BlogPostSection::TYPE_FAQ, 'content' => ['items' => [
                        ['question' => 'Can adults lose a strong foreign accent?', 'answer' => 'Yes. Adults rarely sound identical to childhood bilinguals, but significant improvement is absolutely achievable with structured practice.'],
                        ['question' => 'How often should I practice shadowing?', 'answer' => 'Fifteen minutes of focused shadowing four to five times per week produces noticeable gains within a month.'],
                    ]]],
                ],
            ],
            [
                'slug' => 'my-journey-to-four-languages',
                'title' => 'My Journey to Four Languages',
                'excerpt' => 'A personal roadmap from monolingual beginnings to conversational confidence in four languages — and the mistakes that taught the most.',
                'category' => 'personal',
                'card_layout' => BlogPost::CARD_LAYOUT_TALL,
                'reading_time_minutes' => 9,
                'sort_order' => 3,
                'featured_image_path' => 'https://images.unsplash.com/photo-1488646953014-85cb44e25828?w=1200&q=80',
                'featured_image_alt' => 'Traveler with map and languages',
                'seo_title' => 'My Journey to Four Languages',
                'seo_description' => 'A multilingual learner shares the habits, setbacks, and breakthroughs behind mastering four languages.',
                'seo_keywords' => 'polyglot journey, multilingual, language learning story',
                'sections' => [
                    ['type' => BlogPostSection::TYPE_PARAGRAPH, 'content' => ['text' => 'I did not grow up bilingual. My first language was Nepali, English came through school, and Hindi through community exposure. French and Korean entered later through university and professional curiosity.']],
                    ['type' => BlogPostSection::TYPE_PARAGRAPH, 'content' => ['text' => 'The turning point was treating each language as a living system rather than a subject to pass. Conversations, not grades, became the measure of progress.']],
                    ['type' => BlogPostSection::TYPE_QUOTE, 'content' => [
                        'text' => 'Every new language rewired not just what I could say, but how I listened to the world.',
                        'attribution' => 'Bilingual Vitae® Editorial',
                    ]],
                    ['type' => BlogPostSection::TYPE_IMAGE, 'content' => [
                        'path' => 'https://images.unsplash.com/photo-1523906834658-6e24ef2386f9?w=1200&q=80',
                        'alt' => 'Venice canal representing travel and languages',
                        'placement' => 'full',
                    ]],
                ],
            ],
            [
                'slug' => 'grammar-vs-conversation',
                'title' => 'Grammar vs. Conversation: What to Prioritize',
                'excerpt' => 'Should you master grammar first or jump into conversation? The answer depends on your goals — and your timeline.',
                'category' => 'strategy',
                'card_layout' => BlogPost::CARD_LAYOUT_TALL,
                'reading_time_minutes' => 6,
                'sort_order' => 4,
                'featured_image_path' => 'https://images.unsplash.com/photo-1503676260728-1c00da094a0d?w=1200&q=80',
                'featured_image_alt' => 'Students in a language classroom',
                'seo_title' => 'Grammar vs. Conversation: What to Prioritize',
                'seo_description' => 'Balance grammar study and conversational practice for faster, more confident language learning.',
                'seo_keywords' => 'grammar vs conversation, language learning strategy',
                'sections' => [
                    ['type' => BlogPostSection::TYPE_HEADING, 'content' => ['level' => 2, 'text' => 'When grammar-first works']],
                    ['type' => BlogPostSection::TYPE_PARAGRAPH, 'content' => ['text' => 'Exam preparation, academic writing, and languages with complex morphology reward structured grammar study early. You need a scaffold before you can self-correct.']],
                    ['type' => BlogPostSection::TYPE_HEADING, 'content' => ['level' => 2, 'text' => 'When conversation-first works']],
                    ['type' => BlogPostSection::TYPE_PARAGRAPH, 'content' => ['text' => 'For travel, workplace communication, and confidence building, early speaking prevents the paralysis that years of silent study can create.']],
                    ['type' => BlogPostSection::TYPE_BULLET_LIST, 'content' => ['items' => [
                        'Aim for 70/30 conversation-to-grammar if your goal is employability.',
                        'Use grammar resources to explain errors you already made in speech.',
                        'Alternate focused study weeks with immersion weeks.',
                    ]]],
                    ['type' => BlogPostSection::TYPE_FAQ, 'content' => ['items' => [
                        ['question' => 'Will bad grammar habits stick?', 'answer' => 'Habits can be reshaped with feedback. Early mistakes are easier to fix when you continue structured review alongside speaking.'],
                    ]]],
                ],
            ],
            [
                'slug' => 'why-being-multilingual-changes-your-brain',
                'title' => 'Why Being Multilingual Changes Your Brain',
                'excerpt' => 'Research shows bilingual and multilingual brains develop enhanced executive control, empathy, and cognitive flexibility.',
                'category' => 'science',
                'card_layout' => BlogPost::CARD_LAYOUT_WIDE,
                'reading_time_minutes' => 10,
                'sort_order' => 5,
                'featured_image_path' => 'https://images.unsplash.com/photo-1559757175-08c1dacfbaf0?w=1200&q=80',
                'featured_image_alt' => 'Brain model representing neuroscience',
                'seo_title' => 'Why Being Multilingual Changes Your Brain',
                'seo_description' => 'Explore neuroscience research on how multilingualism shapes attention, memory, and cognitive resilience.',
                'seo_keywords' => 'bilingual brain, neuroscience, multilingual cognition',
                'sections' => [
                    ['type' => BlogPostSection::TYPE_PARAGRAPH, 'content' => ['text' => 'Neuroscience research consistently shows that managing multiple languages strengthens the brain\'s executive control network — the system responsible for focus, task switching, and inhibition of irrelevant information.']],
                    ['type' => BlogPostSection::TYPE_PARAGRAPH, 'content' => ['text' => 'Bilingual individuals often demonstrate advantages in selective attention and conflict monitoring. These benefits appear early in childhood bilinguals and can also emerge in adults who learn later in life.']],
                    ['type' => BlogPostSection::TYPE_IMAGE, 'content' => [
                        'path' => 'https://images.unsplash.com/photo-1576086213369-97a306d3655b?w=1200&q=80',
                        'alt' => 'Scientist reviewing brain scan research',
                        'placement' => 'below',
                    ]],
                    ['type' => BlogPostSection::TYPE_BULLET_LIST, 'content' => ['items' => [
                        'Improved cognitive flexibility in problem-solving tasks.',
                        'Delayed onset of dementia symptoms in some longitudinal studies.',
                        'Greater sensitivity to social and linguistic cues.',
                        'Enhanced metalinguistic awareness across all known languages.',
                    ]]],
                ],
            ],
        ];

        foreach ($posts as $index => $postData) {
            $category = $categories[$postData['category']];
            $sections = $postData['sections'];
            unset($postData['category'], $postData['sections']);

            $post = BlogPost::query()->updateOrCreate(
                ['slug' => $postData['slug']],
                [
                    ...$postData,
                    ...$author,
                    'blog_category_id' => $category->id,
                    'published_at' => now()->subDays(20 - $index),
                    'is_visible' => true,
                ],
            );

            $post->sections()->delete();

            foreach ($sections as $sortOrder => $section) {
                $post->sections()->create([
                    'type' => $section['type'],
                    'content' => $section['content'],
                    'sort_order' => $sortOrder,
                ]);
            }
        }

        ProjectsPageService::forgetCache();
    }
}
