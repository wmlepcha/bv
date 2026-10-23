<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_requirements', function (Blueprint $table) {
            $table->string('review_status')->default('pending');
            $table->text('rejection_note')->nullable();
            $table->string('sample_path')->nullable();
            $table->string('sample_name')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('project_requirements', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reviewed_by');
            $table->dropColumn(['review_status', 'rejection_note', 'sample_path', 'sample_name', 'reviewed_at']);
        });
    }
};
