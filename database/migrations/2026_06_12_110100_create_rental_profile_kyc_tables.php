<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rental_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('label', 40)->default('home');
            $table->string('line1', 255);
            $table->string('line2', 255)->nullable();
            $table->foreignId('city_id')->constrained('cities');
            $table->string('pincode', 10);
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();
            $table->index(['user_id', 'is_default']);
        });

        Schema::create('notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->boolean('push_transactional')->default(true);
            $table->boolean('push_promotional')->default(true);
            $table->boolean('sms_promotional')->default(false);
            $table->boolean('email_promotional')->default(false);
            $table->timestamps();
        });

        Schema::create('rental_kyc_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('document_type', 30); // government_id|driving_license|selfie
            $table->string('document_number_masked', 60)->nullable();
            $table->string('file_path', 255);
            $table->string('file_back_path', 255)->nullable();
            $table->string('provider_ref', 120)->nullable();
            $table->string('status', 20)->default('pending');
            $table->timestamps();
            $table->index(['user_id', 'document_type']);
            $table->index('status');
        });

        Schema::create('rental_kyc_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->string('decision', 20); // approved|rejected
            $table->string('rejection_reason', 255)->nullable();
            $table->string('source', 20)->default('manual'); // auto|manual
            $table->timestamps();
            $table->index('user_id');
            $table->index('decision');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rental_kyc_reviews');
        Schema::dropIfExists('rental_kyc_documents');
        Schema::dropIfExists('notification_preferences');
        Schema::dropIfExists('rental_addresses');
    }
};
