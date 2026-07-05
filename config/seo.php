<?php

return [

    'site_name' => config('app.name'),

    'default_description' => 'Helping people become workplace-ready with strong communication and professional skills through language training, placement support, and migration guidance in Sikkim.',

    'default_og_image' => '/storage/images/bv-logo.png',

    'twitter_handle' => null,

  /*
    |--------------------------------------------------------------------------
    | Indexable marketing pages (included in sitemap.xml)
    |--------------------------------------------------------------------------
    */

    'pages' => [
        'home' => [
            'path' => '/',
            'changefreq' => 'weekly',
            'priority' => '1.0',
        ],
        'about' => [
            'path' => '/about',
            'changefreq' => 'monthly',
            'priority' => '0.8',
        ],
        'services' => [
            'path' => '/services',
            'changefreq' => 'monthly',
            'priority' => '0.9',
        ],
        'projects' => [
            'path' => '/projects',
            'changefreq' => 'monthly',
            'priority' => '0.7',
        ],
    ],

];
