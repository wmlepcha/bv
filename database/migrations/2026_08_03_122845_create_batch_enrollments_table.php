<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('batch_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->constrained('batches')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('transferred_from_batch_id')->nullable()->constrained('batches')->nullOnDelete();
            $table->foreignId('transferred_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('transferred_at')->nullable();
            $table->timestamps();

            $table->unique('student_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('batch_enrollments');
    }
};
