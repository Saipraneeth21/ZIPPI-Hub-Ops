<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rental_incident_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bike_id')->constrained('rental_bikes')->cascadeOnDelete();
            $table->string('incident_type');               // accident, damage, theft, ...
            $table->string('severity')->default('minor');  // minor, moderate, severe, total_loss
            $table->string('status')->default('open');     // open, under_review, resolved, closed
            $table->date('incident_date');
            $table->string('location')->nullable();
            $table->string('reported_by')->nullable();
            $table->text('description');
            $table->unsignedBigInteger('estimated_cost')->default(0); // stored in paise
            $table->string('photo_path')->nullable();       // optional evidence photo
            $table->timestamps();

            $table->index('bike_id');
            $table->index('status');
            $table->index('incident_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rental_incident_reports');
    }
};
