<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Hub Operations — pre-handover capture for a booking pickup.
 * Additive: records what hub staff captured at handover. Does NOT alter the
 * booking lifecycle (the status change is delegated to BookingService::unlock).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hub_handovers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('rental_bookings')->cascadeOnDelete();
            $table->foreignId('hub_staff_id')->nullable()->constrained('hub_staff')->nullOnDelete();
            $table->unsignedTinyInteger('battery_percent')->nullable();
            $table->json('checklist')->nullable();   // {bike_inspected, helmet_issued, ...}
            $table->json('photos')->nullable();       // relative paths on the public disk
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('booking_id');
            $table->index('hub_staff_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hub_handovers');
    }
};
