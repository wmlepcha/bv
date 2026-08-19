<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('material_batch_assignments', function (Blueprint $table) {
            $table->id();
            $table->morphs('assignable');
            $table->foreignId('batch_id')->constrained('batches')->cascadeOnDelete();
            $table->foreignId('assigned_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['assignable_type', 'assignable_id', 'batch_id'], 'material_batch_assignments_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('material_batch_assignments');
    }
};
