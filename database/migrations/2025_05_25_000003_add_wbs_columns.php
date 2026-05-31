<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->decimal('progress_cached', 5, 2)->nullable()->after('units_multiplier');
            $table->string('status')->default('draft')->after('progress_cached');
        });

        Schema::table('sections', function (Blueprint $table) {
            $table->decimal('weight', 8, 4)->nullable()->after('sort');
        });

        Schema::table('items', function (Blueprint $table) {
            $table->decimal('weight', 8, 4)->nullable()->after('required_override');
        });

        Schema::table('catalog_items', function (Blueprint $table) {
            $table->decimal('weight', 8, 4)->nullable()->after('sort');
        });
    }

    public function down(): void
    {
        Schema::table('catalog_items', function (Blueprint $table) {
            $table->dropColumn('weight');
        });

        Schema::table('items', function (Blueprint $table) {
            $table->dropColumn('weight');
        });

        Schema::table('sections', function (Blueprint $table) {
            $table->dropColumn('weight');
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn(['progress_cached', 'status']);
        });
    }
};
