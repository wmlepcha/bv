<?php

use App\Enums\AssignmentStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('assignments')
            ->whereIn('status', [
                AssignmentStatus::PendingApproval->value,
                AssignmentStatus::Rejected->value,
            ])
            ->update([
                'status' => AssignmentStatus::Draft->value,
                'rejection_reason' => null,
            ]);
    }

    public function down(): void
    {
        // Irreversible data migration — legacy approval statuses are no longer used.
    }
};
