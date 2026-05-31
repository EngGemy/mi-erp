<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catalog_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('catalog_section_id')->constrained('catalog_sections')->cascadeOnDelete();
            $table->string('code')->unique();
            $table->string('name');
            $table->decimal('piece_length', 12, 4)->nullable();
            $table->string('unit')->nullable();
            $table->text('formula')->nullable();
            $table->string('scrap_mode')->default('inherit');
            $table->decimal('scrap_percent', 8, 4)->nullable();
            $table->decimal('scrap_fixed', 16, 6)->nullable();
            $table->string('rounding')->default('up');
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalog_items');
    }
};
