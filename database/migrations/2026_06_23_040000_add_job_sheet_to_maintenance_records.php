<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rental_maintenance_records', function (Blueprint $table) {
            $table->text('job_sheet')->nullable()->after('next_service_due');
            $table->json('attachments')->nullable()->after('job_sheet');
        });
    }

    public function down(): void
    {
        Schema::table('rental_maintenance_records', function (Blueprint $table) {
            $table->dropColumn(['job_sheet', 'attachments']);
        });
    }
};
