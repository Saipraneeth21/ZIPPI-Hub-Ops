<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rental_kyc_documents', function (Blueprint $table) {
            // Date of verification — set when a document is approved/rejected.
            $table->timestamp('verified_at')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('rental_kyc_documents', function (Blueprint $table) {
            $table->dropColumn('verified_at');
        });
    }
};
