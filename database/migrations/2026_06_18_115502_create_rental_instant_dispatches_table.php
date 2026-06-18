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
        Schema::create('rental_instant_dispatches', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('mobile', 15);
            // Aadhaar is sensitive PII — stored encrypted, displayed masked.
            $table->text('aadhar_number');
            $table->string('driving_license', 40);
            $table->date('pickup_date');
            $table->string('rental_type', 10); // hourly|daily|monthly
            $table->string('status', 20)->default('pending'); // pending|dispatched|cancelled
            $table->unsignedBigInteger('created_by')->nullable(); // admin who logged it
            $table->timestamps();

            $table->index('status');
            $table->index('mobile');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rental_instant_dispatches');
    }
};
