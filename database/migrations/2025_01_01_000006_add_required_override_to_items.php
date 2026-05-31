<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('items', function (Blueprint $table) {
            // تجاوز يدوي للمطلوب (المرجع في النواقص). فارغ = استخدم الحصر المحسوب (H)
            $table->decimal('required_override', 16, 4)->nullable()->after('rounding');
        });
    }

    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropColumn('required_override');
        });
    }
};
