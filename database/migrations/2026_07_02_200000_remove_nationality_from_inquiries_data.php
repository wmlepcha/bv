<?php

use App\Models\Inquiry;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Inquiry::query()
            ->where('type', 'candidate')
            ->chunkById(100, function ($inquiries): void {
                foreach ($inquiries as $inquiry) {
                    $data = $inquiry->data;

                    if (! is_array($data) || ! array_key_exists('nationality', $data)) {
                        continue;
                    }

                    unset($data['nationality']);

                    $inquiry->update(['data' => $data]);
                }
            });
    }

    public function down(): void
    {
        //
    }
};
