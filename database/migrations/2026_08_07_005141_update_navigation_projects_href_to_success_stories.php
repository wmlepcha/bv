<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('navigation_items')
            ->where('href', '/projects')
            ->update([
                'href' => '/success-stories',
                'label' => 'Success Stories',
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('navigation_items')
            ->where('href', '/success-stories')
            ->update([
                'href' => '/projects',
                'label' => 'Projects',
                'updated_at' => now(),
            ]);
    }
};
