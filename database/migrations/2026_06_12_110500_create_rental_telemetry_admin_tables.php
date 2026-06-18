<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rental_bike_telemetry', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bike_id')->constrained('rental_bikes')->cascadeOnDelete();
            $table->foreignId('booking_id')->nullable()->constrained('rental_bookings')->nullOnDelete();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->decimal('speed_kmph', 5, 2)->nullable();
            $table->unsignedTinyInteger('battery_pct')->nullable();
            $table->boolean('ignition')->nullable();
            $table->dateTime('recorded_at');
            $table->timestamps();
            $table->index(['bike_id', 'recorded_at']);
            $table->index('booking_id');
        });

        Schema::create('rental_lock_commands', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bike_id')->constrained('rental_bikes');
            $table->foreignId('booking_id')->nullable()->constrained('rental_bookings')->nullOnDelete();
            $table->unsignedBigInteger('issued_by')->nullable();
            $table->string('command', 10); // lock|unlock
            $table->string('result', 20)->default('pending');
            $table->timestamps();
            $table->index('bike_id');
            $table->index('booking_id');
        });

        Schema::create('rental_geofence_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bike_id')->constrained('rental_bikes')->cascadeOnDelete();
            $table->foreignId('booking_id')->nullable()->constrained('rental_bookings')->nullOnDelete();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->string('severity', 10)->default('low');
            $table->boolean('resolved')->default(false);
            $table->timestamps();
            $table->index('bike_id');
            $table->index('resolved');
        });

        Schema::create('rental_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('type', 40);
            $table->string('title', 150);
            $table->string('body', 500);
            $table->json('data')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'read_at']);
            $table->index('type');
        });

        Schema::create('rental_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->unique()->constrained('rental_bookings')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('bike_id')->constrained('rental_bikes');
            $table->unsignedTinyInteger('rating');
            $table->string('comment', 1000)->nullable();
            $table->string('status', 20)->default('published');
            $table->unsignedBigInteger('moderated_by')->nullable();
            $table->timestamps();
            $table->index(['bike_id', 'status']);
            $table->index('rating');
        });

        Schema::create('admin_users', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('email', 150)->unique();
            $table->string('password', 255);
            $table->string('role', 20)->default('support');
            $table->string('two_factor_secret', 255)->nullable();
            $table->boolean('is_active')->default(true);
            $table->rememberToken();
            $table->timestamps();
            $table->index('role');
        });

        Schema::create('rental_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('actor_type', 20)->default('system');
            $table->string('action', 60);
            $table->string('entity_type', 60)->nullable();
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->json('before')->nullable();
            $table->json('after')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();
            $table->index(['entity_type', 'entity_id']);
            $table->index('actor_id');
            $table->index('action');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rental_audit_logs');
        Schema::dropIfExists('admin_users');
        Schema::dropIfExists('rental_reviews');
        Schema::dropIfExists('rental_notifications');
        Schema::dropIfExists('rental_geofence_alerts');
        Schema::dropIfExists('rental_lock_commands');
        Schema::dropIfExists('rental_bike_telemetry');
    }
};
