<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->string('driver_name')->nullable()->after('shipped_at');
            $table->string('vehicle_no')->nullable()->after('driver_name');
            $table->string('responsible')->nullable()->after('vehicle_no');
            $table->dateTime('arrival_time')->nullable()->after('responsible');
            $table->text('notes')->nullable()->after('arrival_time');
        });
    }

    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->dropColumn(['driver_name', 'vehicle_no', 'responsible', 'arrival_time', 'notes']);
        });
    }
};
