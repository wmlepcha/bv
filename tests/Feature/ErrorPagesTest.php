<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ErrorPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::get('/_error/{code}', fn (int $code) => abort($code))->middleware('web');
    }

    /**
     * @return array<string, array{int, string}>
     */
    public static function pages(): array
    {
        return [
            '403' => [403, 'This door needs a different key'],
            '404' => [404, 'Lost in translation'],
            '419' => [419, 'That page got tired of waiting'],
            '429' => [429, 'Easy there, champion'],
            '500' => [500, 'We tripped over a der, die or das'],
            '503' => [503, 'Brushing up on our grammar'],
        ];
    }

    #[DataProvider('pages')]
    public function test_each_error_status_renders_its_branded_page(int $code, string $headline): void
    {
        $this->get("/_error/{$code}")
            ->assertStatus($code)
            ->assertSee($headline)
            ->assertSee((string) $code)
            ->assertSee('noindex', false);
    }

    public function test_retryable_errors_offer_a_refresh_button_and_the_rest_offer_going_back(): void
    {
        $this->get('/_error/500')->assertSee('Try again')->assertDontSee('Go back');
        $this->get('/_error/419')->assertSee('Refresh and try again');
        $this->get('/_error/404')->assertSee('Go back')->assertSee('Take me home');
    }

    public function test_the_home_button_matches_who_is_looking(): void
    {
        $this->get('/_error/404')->assertSee('href="'.url('/').'"', false);

        $this->actingAs(User::factory()->recruitmentManager()->create())->get('/_error/404')
            ->assertSee(route('recruitment-manager.dashboard'), false)
            ->assertSee('Back to my dashboard');

        $this->actingAs(User::factory()->trainingManager()->create())->get('/_error/404')
            ->assertSee(route('training-manager.dashboard'), false);

        $this->actingAs(User::factory()->student()->create())->get('/_error/404')
            ->assertSee(route('dashboard'), false);
    }

    public function test_real_missing_pages_and_forbidden_areas_use_the_new_pages(): void
    {
        $this->get('/recruitment-manager')->assertNotFound()->assertSee('Lost in translation');

        $this->actingAs(User::factory()->student()->create())
            ->get(route('recruitment-manager.dashboard'))
            ->assertForbidden()
            ->assertSee('This door needs a different key');

    }

    public function test_pages_do_not_depend_on_built_assets(): void
    {
        $html = $this->get('/_error/404')->getContent();

        $this->assertStringNotContainsString('/build/assets', $html);
        $this->assertStringNotContainsString('@vite', $html);
    }
}
