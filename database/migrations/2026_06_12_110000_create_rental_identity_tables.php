<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // users table is extended in the base create_users_table migration
        // (mobile/country_code added, email/password made nullable) since
        // SQLite does not support column ->change() reliably.

        Schema::create('cities', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('state', 120)->nullable();
            $table->string('country', 2)->default('IN');
            $table->string('timezone', 64)->default('Asia/Kolkata');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index('is_active');
        });

        Schema::create('rental_user_profiles', function (Blueprint $table) {
            $table->foreignId('user_id')->primary()->constrained('users')->cascadeOnDelete();
            $table->string('kyc_status', 20)->default('none');
            $table->boolean('is_blocked')->default(false);
            $table->string('blocked_reason', 255)->nullable();
            $table->foreignId('default_city_id')->nullable()->constrained('cities')->nullOnDelete();
            $table->string('profile_photo_path', 255)->nullable();
            $table->timestamps();
            $table->index('kyc_status');
            $table->index('is_blocked');
        });

        Schema::create('otp_verifications', function (Blueprint $table) {
            $table->id();
            $table->string('otp_token', 64)->unique();
            $table->string('mobile', 15);
            $table->string('otp_hash', 255);
            $table->string('purpose', 30)->default('login');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->unsignedTinyInteger('resends')->default(0);
            $table->timestamp('expires_at');
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
            $table->index(['mobile', 'purpose']);
            $table->index('expires_at');
        });

        Schema::create('device_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('fcm_token', 255)->unique();
            $table->string('platform', 10);
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_tokens');
        Schema::dropIfExists('otp_verifications');
        Schema::dropIfExists('rental_user_profiles');
        Schema::dropIfExists('cities');
    }
};
