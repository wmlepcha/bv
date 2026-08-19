<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('home_highlights', function (Blueprint $table) {
            $table->string('group')->default('services')->after('label');
        });
    }

    public function down(): void
    {
        Schema::table('home_highlights', function (Blueprint $table) {
            $table->dropColumn('group');
        });
    }
};
