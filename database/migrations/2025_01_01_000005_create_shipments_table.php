<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // الحمولات (أعمدة التوريد في شيت النواقص)
        Schema::create('shipments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('name');             // حموله 1 ...
            $table->date('shipped_at')->nullable();
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        // الكميات المُسلّمة لكل صنف في كل حمولة
        Schema::create('shipment_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->constrained()->cascadeOnDelete();
            $table->decimal('quantity', 16, 4)->default(0);
            $table->timestamps();

            $table->unique(['shipment_id', 'item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipment_items');
        Schema::dropIfExists('shipments');
    }
};
