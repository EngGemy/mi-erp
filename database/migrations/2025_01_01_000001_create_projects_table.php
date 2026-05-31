<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('name');                         // اسم المشروع (كراون - تسمين)
            $table->string('code')->unique();               // كود المشروع
            $table->text('description')->nullable();
            // نسبة الهالك الافتراضية على مستوى المشروع (تتجاوز إعداد النظام إن وُجدت)
            $table->decimal('default_scrap_percent', 8, 4)->nullable();
            // مضاعف الإجمالي النهائي (عدد العنابر مثلاً = 2 في ملف كراون عبر *2)
            $table->decimal('units_multiplier', 12, 4)->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
