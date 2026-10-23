<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assignment_sections', function (Blueprint $table) {
            $table->string('label', 40)->change();
        });
    }

    public function down(): void
    {
        Schema::table('assignment_sections', function (Blueprint $table) {
            $table->string('label', 10)->change();
        });
    }
};
