<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rental_coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->unique();
            $table->string('type', 12); // percent|flat
            $table->unsignedBigInteger('value'); // percent basis points OR paise
            $table->unsignedBigInteger('max_discount')->nullable(); // paise cap
            $table->unsignedBigInteger('min_booking_amount')->nullable(); // paise
            $table->integer('usage_limit_total')->nullable();
            $table->integer('usage_limit_per_user')->default(1);
            $table->integer('used_count')->default(0);
            $table->dateTime('valid_from');
            $table->dateTime('valid_to');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['is_active', 'valid_from', 'valid_to']);
        });

        Schema::create('rental_bookings', function (Blueprint $table) {
            $table->id();
            $table->string('booking_code', 20)->unique();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('bike_id')->constrained('rental_bikes');
            $table->foreignId('pickup_hub_id')->constrained('rental_hubs');
            $table->foreignId('return_hub_id')->constrained('rental_hubs');
            $table->foreignId('city_id')->constrained('cities');
            $table->string('duration_type', 10);
            $table->dateTime('start_at');
            $table->dateTime('end_at');
            $table->dateTime('actual_start_at')->nullable();
            $table->dateTime('actual_end_at')->nullable();
            $table->decimal('computed_units', 10, 2)->default(0);
            $table->unsignedBigInteger('base_amount')->default(0);
            $table->unsignedBigInteger('tax_amount')->default(0);
            $table->unsignedBigInteger('platform_fee')->default(0);
            $table->unsignedBigInteger('discount_amount')->default(0);
            $table->unsignedBigInteger('deposit_amount')->default(0);
            $table->unsignedBigInteger('total_amount')->default(0);
            $table->foreignId('coupon_id')->nullable()->constrained('rental_coupons')->nullOnDelete();
            $table->string('status', 20)->default('pending');
            $table->string('cancellation_reason', 255)->nullable();
            $table->unsignedBigInteger('late_penalty')->default(0);
            $table->dateTime('hold_expires_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'status']);
            $table->index(['bike_id', 'status']);
            $table->index(['bike_id', 'start_at', 'end_at']);
            $table->index('status');
        });

        Schema::create('rental_booking_status_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('rental_bookings')->cascadeOnDelete();
            $table->string('from_status', 20)->nullable();
            $table->string('to_status', 20);
            $table->unsignedBigInteger('changed_by')->nullable();
            $table->string('note', 255)->nullable();
            $table->timestamps();
            $table->index('booking_id');
        });

        Schema::create('rental_recent_searches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('bike_id')->nullable()->constrained('rental_bikes')->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('rental_bike_categories')->nullOnDelete();
            $table->string('duration_type', 10)->nullable();
            $table->json('search_payload')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rental_recent_searches');
        Schema::dropIfExists('rental_booking_status_history');
        Schema::dropIfExists('rental_bookings');
        Schema::dropIfExists('rental_coupons');
    }
};
