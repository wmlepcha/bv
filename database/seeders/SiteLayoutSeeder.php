<?php

namespace Database\Seeders;

use App\Models\NavigationItem;
use App\Models\OfficeLocation;
use App\Models\PartnerLogo;
use App\Models\SiteSetting;
use App\Models\SocialLink;
use Illuminate\Database\Seeder;

class SiteLayoutSeeder extends Seeder
{
    public function run(): void
    {
        SiteSetting::setValue('header.logo_path', '/storage/images/bv-logo.png');
        SiteSetting::setValue(
            'header.partner_label',
            'Official partner of Government of Sikkim and NSDC International',
        );
        SiteSetting::setValue('footer.contact_email', 'hello@bilingualvitae.com');
        SiteSetting::setValue('footer.contact_phone', '+91 8250312500');
        SiteSetting::setValue('footer.copyright', 'Bilingual Vitae. All rights reserved.');

        $navigationItems = [
            ['label' => 'Home', 'href' => '/', 'icon' => 'home', 'sort_order' => 0],
            ['label' => 'About Us', 'href' => '/about', 'icon' => 'users', 'sort_order' => 1],
            ['label' => 'Services', 'href' => '/services', 'icon' => 'briefcase', 'sort_order' => 2],
            ['label' => 'Projects', 'href' => '/projects', 'icon' => 'folder-kanban', 'sort_order' => 3],
        ];

        foreach ($navigationItems as $item) {
            NavigationItem::query()->updateOrCreate(
                ['href' => $item['href']],
                [...$item, 'is_visible' => true],
            );
        }

        $partnerLogos = [
            [
                'name' => 'Government of Sikkim',
                'image_path' => '/storage/images/government-of-sikkim.png',
                'alt_text' => 'Government of Sikkim',
                'sort_order' => 0,
            ],
            [
                'name' => 'NSDC International',
                'image_path' => '/storage/images/logo_nsdc.svg',
                'alt_text' => 'NSDC International',
                'sort_order' => 1,
            ],
        ];

        foreach ($partnerLogos as $partner) {
            PartnerLogo::query()->updateOrCreate(
                ['name' => $partner['name']],
                [...$partner, 'is_visible' => true],
            );
        }

        $offices = [
            [
                'name' => 'Head Office',
                'address' => '3rd floor, Home Square Complex, Near Salugara Monastery, Salugara, Siliguri, West Bengal, India, 734008.',
                'sort_order' => 0,
            ],
            [
                'name' => 'Sikkim Unit',
                'address' => 'Gyaltsen Building, Metro Point, Tadong, beside Zudio Showroom and Mount Zion School, Metro Point, Tadong, Gangtok, Sikkim 737102.',
                'sort_order' => 1,
            ],
            [
                'name' => 'West Bengal Unit',
                'address' => 'Ground floor, Aum Residencies, Kashibari Road, Dagapur, PS - Pradhan Nagar, Post - Salbari Siliguri, Near Blue Mountain Country Club, Dagapur, Siliguri, West Bengal, 734002.',
                'sort_order' => 2,
            ],
        ];

        foreach ($offices as $office) {
            OfficeLocation::query()->updateOrCreate(
                ['name' => $office['name']],
                [...$office, 'is_visible' => true],
            );
        }

        $socialLinks = [
            ['label' => 'LinkedIn', 'url' => '#', 'sort_order' => 0],
            ['label' => 'Instagram', 'url' => '#', 'sort_order' => 1],
            ['label' => 'Facebook', 'url' => '#', 'sort_order' => 2],
            ['label' => 'YouTube', 'url' => '#', 'sort_order' => 3],
        ];

        foreach ($socialLinks as $link) {
            SocialLink::query()->updateOrCreate(
                ['label' => $link['label']],
                [...$link, 'is_visible' => true],
            );
        }
    }
}
