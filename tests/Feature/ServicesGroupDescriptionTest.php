<?php

namespace Tests\Feature;

use App\Filament\Resources\ServiceGroups\Pages\EditServiceGroup;
use App\Models\ServiceCategory;
use App\Models\ServiceGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Livewire\Livewire;
use Tests\TestCase;

class ServicesGroupDescriptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_group_description_is_sent_to_the_services_page_and_is_optional(): void
    {
        $category = ServiceCategory::query()->create([
            'title' => 'Placement',
            'slug' => 'placement',
            'sort_order' => 1,
            'is_visible' => true,
        ]);

        foreach ([['Registered Nurse', 'Pathways for qualified nurses.'], ['Caregiver', null]] as $order => [$title, $description]) {
            ServiceGroup::query()->create([
                'service_category_id' => $category->id,
                'title' => $title,
                'slug' => str($title)->slug()->toString(),
                'description' => $description,
                'sort_order' => $order,
                'is_visible' => true,
            ]);
        }

        $this->get('/services')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('servicesPage.categories.0.groups.0.description', 'Pathways for qualified nurses.')
                ->where('servicesPage.categories.0.groups.1.description', null));
    }

    public function test_admin_can_edit_the_description_in_filament_and_the_page_updates(): void
    {
        $category = ServiceCategory::query()->create(['title' => 'Placement', 'slug' => 'placement', 'sort_order' => 1, 'is_visible' => true]);
        $group = ServiceGroup::query()->create([
            'service_category_id' => $category->id,
            'title' => 'Registered Nurse',
            'slug' => 'registered-nurse',
            'sort_order' => 1,
            'is_visible' => true,
        ]);

        // Prime the page cache, then edit through the admin form.
        $this->get('/services')->assertInertia(fn (Assert $page) => $page->where('servicesPage.categories.0.groups.0.description', null));

        Livewire::actingAs(User::factory()->admin()->create())
            ->test(EditServiceGroup::class, ['record' => $group->getKey()])
            ->fillForm(['description' => 'Edited from the admin.'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->get('/services')->assertInertia(fn (Assert $page) => $page
            ->where('servicesPage.categories.0.groups.0.description', 'Edited from the admin.'));
    }

    public function test_description_is_limited_to_500_characters(): void
    {
        $category = ServiceCategory::query()->create(['title' => 'Placement', 'slug' => 'placement', 'sort_order' => 1, 'is_visible' => true]);
        $group = ServiceGroup::query()->create(['service_category_id' => $category->id, 'title' => 'Registered Nurse', 'slug' => 'registered-nurse', 'sort_order' => 1, 'is_visible' => true]);

        Livewire::actingAs(User::factory()->admin()->create())
            ->test(EditServiceGroup::class, ['record' => $group->getKey()])
            ->fillForm(['description' => str_repeat('a', 501)])
            ->call('save')
            ->assertHasFormErrors(['description' => 'max']);
    }
}
