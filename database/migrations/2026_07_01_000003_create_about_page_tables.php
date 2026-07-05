<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('about_paragraphs', function (Blueprint $table) {
            $table->id();
            $table->string('section');
            $table->text('body');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_visible')->default(true);
            $table->timestamps();
        });

        Schema::create('about_core_values', function (Blueprint $table) {
            $table->id();
            $table->string('letter', 1);
            $table->string('title');
            $table->text('description');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_visible')->default(true);
            $table->timestamps();
        });

        Schema::create('about_journey_milestones', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('period');
            $table->string('title');
            $table->string('layout')->default('center');
            $table->string('image_path')->nullable();
            $table->string('image_alt')->nullable();
            $table->json('bullets')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_visible')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('about_journey_milestones');
        Schema::dropIfExists('about_core_values');
        Schema::dropIfExists('about_paragraphs');
    }
};
