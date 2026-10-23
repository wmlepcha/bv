<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('dashboard_activated_at')->nullable()->after('registration_paid_at');
        });

        DB::table('users')
            ->where('role', 'student')
            ->whereNotNull('registration_paid_at')
            ->update(['dashboard_activated_at' => DB::raw('registration_paid_at')]);

        Schema::table('course_feedback', function (Blueprint $table) {
            $table->unsignedTinyInteger('form_number')->default(1)->after('center_id');
        });

        $formNumbers = [];

        DB::table('course_feedback')->orderBy('created_at')->orderBy('id')->get(['id', 'student_id'])
            ->each(function (object $feedback) use (&$formNumbers): void {
                $formNumbers[$feedback->student_id] = ($formNumbers[$feedback->student_id] ?? 0) + 1;

                DB::table('course_feedback')->where('id', $feedback->id)->update(['form_number' => $formNumbers[$feedback->student_id]]);
            });

        Schema::table('course_feedback', function (Blueprint $table) {
            $table->unique(['student_id', 'form_number']);
            $table->dropUnique(['student_id', 'level']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('course_feedback', function (Blueprint $table) {
            $table->unique(['student_id', 'level']);
            $table->dropUnique(['student_id', 'form_number']);
            $table->dropColumn('form_number');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('dashboard_activated_at');
        });
    }
};
