<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('centers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        $now = now();

        foreach ([
            ['name' => 'Gangtok', 'sort_order' => 1],
            ['name' => 'Siliguri', 'sort_order' => 2],
            ['name' => 'Manipur', 'sort_order' => 3],
        ] as $center) {
            DB::table('centers')->insert([
                'name' => $center['name'],
                'slug' => Str::slug($center['name']),
                'sort_order' => $center['sort_order'],
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('centers');
    }
};
