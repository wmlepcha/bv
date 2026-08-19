<?php

namespace Database\Seeders;

use App\Models\User;
use App\Services\AboutPageService;
use App\Services\HomePageService;
use App\Services\InquiryOptionsService;
use App\Services\ProjectsPageService;
use App\Services\ServicesPageService;
use App\Services\SiteLayoutService;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->admin()->create([
            'name' => 'Admin User',
            'email' => 'admin@bilingualvitae.com',
        ]);

        $this->call(SiteLayoutSeeder::class);
        $this->call(HomePageSeeder::class);
        $this->call(AboutPageSeeder::class);
        $this->call(ServicesPageSeeder::class);
        $this->call(ProjectsPageSeeder::class);
        $this->call(EducationLevelSeeder::class);
        $this->call(CenterSeeder::class);

        HomePageService::forgetCache();
        SiteLayoutService::forgetCache();
        AboutPageService::forgetCache();
        ServicesPageService::forgetCache();
        ProjectsPageService::forgetCache();
        InquiryOptionsService::forgetCache();
    }
}
