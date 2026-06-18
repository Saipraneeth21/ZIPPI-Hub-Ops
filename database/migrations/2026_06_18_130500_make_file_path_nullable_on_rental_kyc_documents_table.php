<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rental_kyc_documents', function (Blueprint $table) {
            // Allow editing a document's verification metadata without forcing a
            // file re-upload (the admin UI manages the file separately).
            $table->string('file_path')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('rental_kyc_documents', function (Blueprint $table) {
            $table->string('file_path')->nullable(false)->change();
        });
    }
};
