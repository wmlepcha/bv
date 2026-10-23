<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recruiter_project_candidates', function (Blueprint $table) {
            $table->timestamp('sent_to_recruiter_at')->nullable();
        });

        // Entries BV already approved under the old one-step flow count as sent.
        DB::table('recruiter_project_candidates')
            ->whereNotNull('approved_at')
            ->update(['sent_to_recruiter_at' => DB::raw('approved_at')]);
    }

    public function down(): void
    {
        Schema::table('recruiter_project_candidates', function (Blueprint $table) {
            $table->dropColumn('sent_to_recruiter_at');
        });
    }
};
