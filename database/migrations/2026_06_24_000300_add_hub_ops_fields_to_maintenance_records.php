<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Hub Operations — additive fields so hub staff can file maintenance tickets
 * with a tracked status + free-text description, while the existing admin
 * MaintenanceRecord flow keeps using maintenance_type/attachments/job_sheet.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rental_maintenance_records', function (Blueprint $table) {
            $table->string('status')->default('open')->after('maintenance_type'); // open|in_progress|completed
            $table->text('description')->nullable()->after('status');
            $table->unsignedBigInteger('reported_by_hub_staff_id')->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('rental_maintenance_records', function (Blueprint $table) {
            $table->dropColumn(['status', 'description', 'reported_by_hub_staff_id']);
        });
    }
};
