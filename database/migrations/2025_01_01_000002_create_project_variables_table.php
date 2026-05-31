<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_variables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            // المفتاح المستخدم داخل المعادلات (لاتيني بدون مسافات): tiers, lines, cages
            $table->string('key');
            // الاسم الظاهر بالعربية: عدد الأدوار
            $table->string('label');
            $table->decimal('value', 16, 6)->default(0);
            $table->string('unit')->nullable();             // وحدة (متر/عدد)
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();

            $table->unique(['project_id', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_variables');
    }
};
