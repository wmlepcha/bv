<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('material_folders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('material_folders')->cascadeOnDelete();
            $table->string('name');
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['parent_id', 'name']);
        });

        Schema::create('material_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('folder_id')->constrained('material_folders')->cascadeOnDelete();
            $table->string('original_name');
            $table->string('display_name');
            $table->string('disk')->default('local');
            $table->string('path');
            $table->string('mime_type')->nullable();
            $table->string('extension', 16);
            $table->unsignedBigInteger('size')->default(0);
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->index('folder_id');
        });

        Schema::create('material_assignments', function (Blueprint $table) {
            $table->id();
            $table->morphs('assignable');
            $table->foreignId('trainer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('assigned_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['assignable_type', 'assignable_id', 'trainer_id'], 'material_assignments_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('material_assignments');
        Schema::dropIfExists('material_files');
        Schema::dropIfExists('material_folders');
    }
};
