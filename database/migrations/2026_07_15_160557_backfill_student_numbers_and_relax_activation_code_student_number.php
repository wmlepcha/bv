<?php

use App\Enums\UserRole;
use App\Models\User;
use App\Services\StudentNumberGenerator;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activation_codes', function (Blueprint $table) {
            $table->dropUnique(['student_number']);
        });

        $generator = app(StudentNumberGenerator::class);

        User::query()
            ->where('role', UserRole::Student)
            ->whereNull('student_number')
            ->whereNotNull('state_code')
            ->whereNotNull('special_id')
            ->whereNotNull('registration_year')
            ->orderBy('id')
            ->each(function (User $user) use ($generator): void {
                $user->forceFill([
                    'student_number' => $generator->generateForUser($user),
                ])->save();
            });
    }

    public function down(): void
    {
        Schema::table('activation_codes', function (Blueprint $table) {
            $table->unique('student_number');
        });
    }
};
