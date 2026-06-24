<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Hub Operations — return capture for a booking completion.
 * Additive: records odometer/battery/photos/damage at return. The money math
 * (late penalty, deposit refund) is delegated to BookingService::returnBike.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hub_returns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('rental_bookings')->cascadeOnDelete();
            $table->foreignId('hub_staff_id')->nullable()->constrained('hub_staff')->nullOnDelete();
            $table->unsignedInteger('odometer_reading')->nullable();
            $table->unsignedTinyInteger('battery_percent')->nullable();
            $table->json('photos')->nullable();       // relative paths on the public disk
            $table->text('damage_notes')->nullable();
            $table->timestamps();

            $table->index('booking_id');
            $table->index('hub_staff_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hub_returns');
    }
};
