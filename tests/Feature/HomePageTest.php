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
                    ->has('stats', 5)
                    ->has('highlights', 5)
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
                ->has('homePage.seo', fn (Assert $seo) => $seo
                    ->where('title', 'Language Training & Career Support in Sikkim')
                    ->etc(),
                ),
            );
    }
}
