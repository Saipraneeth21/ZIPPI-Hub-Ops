<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('rental_bike_pricing', function (Blueprint $table) {
            $table->unsignedBigInteger('weekly_rate')->nullable()->after('daily_rate'); // paise/week
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rental_bike_pricing', function (Blueprint $table) {
            $table->dropColumn('weekly_rate');
        });
    }
};
