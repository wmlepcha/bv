<?php

use App\Enums\UserRole;
use App\Models\User;
use App\Support\RegistrationFeeAmount;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('description')->nullable();
            $table->unsignedInteger('price_rupees');
            $table->unsignedSmallInteger('duration_months');
            $table->json('features');
            $table->string('badge_text')->nullable();
            $table->boolean('is_recommended')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('registration_expires_at')->nullable()->after('registration_fee_paid_via');
            $table->foreignId('registration_plan_id')->nullable()->after('registration_expires_at')->constrained('plans')->nullOnDelete();
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('plan_id')->nullable()->after('user_id')->constrained('plans')->nullOnDelete();
            $table->string('plan_name')->nullable()->after('plan_id');
            $table->unsignedSmallInteger('duration_months')->nullable()->after('plan_name');
        });

        $twoYearPrice = RegistrationFeeAmount::amountInRupees();

        $oneYearFeatures = [
            'Access to live classes',
            'CV profile builder',
            'Training PDFs and materials',
            'Dashboard notifications',
            '12 months of full access',
        ];

        $twoYearFeatures = [
            'Access to live classes',
            'CV profile builder',
            'Training PDFs and materials',
            'Dashboard notifications',
            '24 months of full access',
        ];

        $oneYearPlanId = DB::table('plans')->insertGetId([
            'name' => '1 Year',
            'description' => 'Full dashboard access for one year.',
            'price_rupees' => 11800,
            'duration_months' => 12,
            'features' => json_encode($oneYearFeatures),
            'badge_text' => null,
            'is_recommended' => false,
            'sort_order' => 1,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $twoYearPlanId = DB::table('plans')->insertGetId([
            'name' => '2 Year',
            'description' => 'Full dashboard access for two years.',
            'price_rupees' => $twoYearPrice,
            'duration_months' => 24,
            'features' => json_encode($twoYearFeatures),
            'badge_text' => 'Best value',
            'is_recommended' => true,
            'sort_order' => 2,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        User::query()
            ->where('role', UserRole::Student)
            ->whereNotNull('registration_paid_at')
            ->whereNull('registration_expires_at')
            ->eachById(function (User $user) use ($twoYearPlanId): void {
                $user->forceFill([
                    'registration_expires_at' => $user->registration_paid_at->copy()->addYears(2),
                    'registration_plan_id' => $twoYearPlanId,
                ])->save();
            });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('plan_id');
            $table->dropColumn(['plan_name', 'duration_months']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('registration_plan_id');
            $table->dropColumn('registration_expires_at');
        });

        Schema::dropIfExists('plans');
    }
};
