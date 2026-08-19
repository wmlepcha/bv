<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('material_files', function (Blueprint $table) {
            $table->string('kind', 16)->default('file')->after('folder_id');
            $table->string('url')->nullable()->after('path');
            $table->string('disk')->nullable()->default(null)->change();
            $table->string('path')->nullable()->change();
            $table->string('mime_type')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('material_files', function (Blueprint $table) {
            $table->dropColumn(['kind', 'url']);
            $table->string('disk')->default('local')->nullable(false)->change();
            $table->string('path')->nullable(false)->change();
            $table->string('mime_type')->nullable()->change();
        });
    }
};
