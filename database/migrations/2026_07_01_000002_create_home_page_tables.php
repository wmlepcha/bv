<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('home_stats', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('value');
            $table->string('suffix')->default('');
            $table->string('label');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_visible')->default(true);
            $table->timestamps();
        });

        Schema::create('home_highlights', function (Blueprint $table) {
            $table->id();
            $table->string('label');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_visible')->default(true);
            $table->timestamps();
        });

        Schema::create('home_feature_items', function (Blueprint $table) {
            $table->id();
            $table->string('section');
            $table->string('label');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_visible')->default(true);
            $table->timestamps();
        });

        Schema::create('home_testimonials', function (Blueprint $table) {
            $table->id();
            $table->string('section');
            $table->text('quote');
            $table->string('author');
            $table->string('role');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_visible')->default(true);
            $table->timestamps();
        });

        Schema::create('home_service_cards', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('href');
            $table->string('image_path');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_visible')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('home_service_cards');
        Schema::dropIfExists('home_testimonials');
        Schema::dropIfExists('home_feature_items');
        Schema::dropIfExists('home_highlights');
        Schema::dropIfExists('home_stats');
    }
};
