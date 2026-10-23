<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('material_files', function (Blueprint $table) {
            $table->json('playlist_items')->nullable()->after('url');
        });
    }

    public function down(): void
    {
        Schema::table('material_files', function (Blueprint $table) {
            $table->dropColumn('playlist_items');
        });
    }
};
