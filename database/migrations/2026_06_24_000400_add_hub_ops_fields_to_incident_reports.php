<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Hub Operations — additive fields so hub staff can link an incident to a
 * booking and attach multiple photos. Existing single photo_path + the rest of
 * the incident schema are untouched.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rental_incident_reports', function (Blueprint $table) {
            $table->unsignedBigInteger('booking_id')->nullable()->after('bike_id');
            $table->unsignedBigInteger('reported_by_hub_staff_id')->nullable()->after('reported_by');
            $table->json('photos')->nullable()->after('photo_path');

            $table->index('booking_id');
        });
    }

    public function down(): void
    {
        Schema::table('rental_incident_reports', function (Blueprint $table) {
            $table->dropIndex(['booking_id']);
            $table->dropColumn(['booking_id', 'reported_by_hub_staff_id', 'photos']);
        });
    }
};
