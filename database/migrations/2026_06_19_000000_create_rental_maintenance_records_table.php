<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rental_maintenance_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bike_id')->constrained('rental_bikes')->cascadeOnDelete();
            $table->string('maintenance_type');
            $table->date('maintenance_date');
            $table->unsignedBigInteger('cost')->default(0); // stored in paise
            $table->unsignedInteger('odometer_reading')->nullable();
            $table->date('next_service_due')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rental_maintenance_records');
    }
};
