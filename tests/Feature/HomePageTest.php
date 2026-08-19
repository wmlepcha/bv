<?php

namespace Tests\Feature;

use Database\Seeders\HomePageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class HomePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_returns_dynamic_content(): void
    {
        $this->seed(HomePageSeeder::class);

        $this->get(route('home'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('marketing/home')
                ->has('homePage.hero', fn (Assert $hero) => $hero
                    ->where('badge', 'Global workforce readiness')
                    ->where('headline', 'People flex their linguistic & professional skills here…')
                    ->where('cardText', 'With us language becomes your bridge, not a barrier.')
                    ->has('stats', 5)
                    ->has('highlightColumns', 2)
                    ->has('highlightColumns.0.items', 3)
                    ->has('highlightColumns.1.items', 2)
                    ->where('highlightColumns.0.heading', 'Software suite')
                    ->where('highlightColumns.1.heading', 'Services')
                    ->etc(),
                )
                ->has('homePage.crafting', fn (Assert $crafting) => $crafting
                    ->where('heading', 'Crafting people. Linguistically & Professionally.')
                    ->has('pillars', 4)
                    ->has('testimonial')
                    ->etc(),
                )
                ->has('homePage.recruiters', fn (Assert $recruiters) => $recruiters
                    ->where('heading', 'Recruiters never leave unsatisfied.')
                    ->has('benefits', 4)
                    ->has('testimonial')
                    ->etc(),
                )
                ->has('homePage.services', fn (Assert $services) => $services
                    ->has('cards', 3)
                    ->etc(),
                )
                ->has('homePage.programs', fn (Assert $programs) => $programs
                    ->where('badge', 'Our Programs')
                    ->where('heading', 'Boost your career with our expertise')
                    ->where('ctaLabel', 'View All Programs')
                    ->has('cards', 2)
                    ->has('cards.0', fn (Assert $card) => $card
                        ->where('title', 'Language & Employability Training Programs')
                        ->has('description')
                        ->has('href')
                        ->has('imageUrl')
                        ->etc(),
                    )
                    ->etc(),
                )
                ->has('homePage.seo', fn (Assert $seo) => $seo
                    ->where('title', 'Language Training & Career Support in Sikkim')
                    ->etc(),
                ),
            );
    }
}
