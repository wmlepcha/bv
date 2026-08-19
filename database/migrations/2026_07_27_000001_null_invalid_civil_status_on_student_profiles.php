<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('student_profiles')
            ->whereNotNull('civil_status')
            ->whereNotIn('civil_status', ['Unmarried', 'Married'])
            ->update(['civil_status' => null]);
    }

    public function down(): void
    {
        // Irreversible data cleanup.
    }
};
