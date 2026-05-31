<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_item_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();
            $table->foreignId('stage_id')->constrained('stages')->cascadeOnDelete();
            $table->decimal('done_qty', 16, 4)->default(0);
            $table->timestamps();

            $table->unique(['item_id', 'stage_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_item_progress');
    }
};
