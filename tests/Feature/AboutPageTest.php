<?php

namespace Tests\Feature;

use Database\Seeders\AboutPageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AboutPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_about_page_returns_dynamic_content(): void
    {
        $this->seed(AboutPageSeeder::class);

        $this->get(route('about'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('marketing/about')
                ->has('aboutPage.hero', fn (Assert $hero) => $hero
                    ->where('title', 'About Us')
                )
                ->has('aboutPage.overview', fn (Assert $overview) => $overview
                    ->where('tagline', 'Bilingual Vitae, An EdTech and Future Work Company.')
                    ->has('paragraphs', 3)
                )
                ->where('aboutPage.mission', fn (string $mission) => str_contains($mission, 'last mile'))
                ->where('aboutPage.vision', fn (string $vision) => str_contains($vision, 'bilingual proficiency'))
                ->has('aboutPage.leapfrog', fn (Assert $leapfrog) => $leapfrog
                    ->where('title', 'LEAPFROG')
                    ->has('paragraphs', 3)
                    ->etc()
                )
                ->has('aboutPage.coreValues', fn (Assert $coreValues) => $coreValues
                    ->has('intro', 2)
                    ->has('values', 8)
                )
                ->has('aboutPage.impact', fn (Assert $impact) => $impact
                    ->whereType('paragraph', 'string')
                    ->whereType('closing', 'string')
                )
                ->has('aboutPage.journey', fn (Assert $journey) => $journey
                    ->has('milestones', 4)
                )
                ->has('aboutPage.seo', fn (Assert $seo) => $seo
                    ->where('title', 'About Us')
                    ->etc()
                ),
            );
    }
}
