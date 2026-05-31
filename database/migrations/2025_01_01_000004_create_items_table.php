<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('section_id')->nullable()->constrained()->nullOnDelete();

            // الكود اللاتيني الفريد داخل المشروع - يُستخدم للإشارة من معادلات أصناف أخرى
            $table->string('code');
            // بيان الصنف بالعربية
            $table->string('name');
            $table->decimal('piece_length', 12, 4)->nullable();  // طول القطعة (سم/متر)
            $table->string('unit')->nullable();

            // المعادلة الأساسية للكمية الصافية (نص ExpressionLanguage)
            // مثال: ((cages + 2) * 2) * lines
            // ويمكن أن تشير لصنف آخر: item('omega_3m') * 8
            $table->text('formula')->nullable();

            // --- إدارة الهالك/الزيادة (يرث من المشروع ثم النظام) ---
            // scrap_mode: inherit | percent | fixed | formula | none
            $table->string('scrap_mode')->default('inherit');
            $table->decimal('scrap_percent', 8, 4)->nullable();  // عند percent
            $table->decimal('scrap_fixed', 16, 6)->nullable();   // عند fixed (+12 قطعة)
            $table->text('scrap_formula')->nullable();           // عند formula

            // التقريب: none | up | nearest  (للأعداد الصحيحة في التصنيع)
            $table->string('rounding')->default('up');

            $table->unsignedInteger('sort')->default(0);
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['project_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};
