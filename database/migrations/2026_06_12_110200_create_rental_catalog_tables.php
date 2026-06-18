<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rental_hubs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('city_id')->constrained('cities');
            $table->string('name', 150);
            $table->string('address', 255);
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->json('geofence_polygon')->nullable();
            $table->time('opening_time')->default('06:00:00');
            $table->time('closing_time')->default('22:00:00');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['city_id', 'is_active']);
        });

        Schema::create('rental_bike_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('slug', 120)->unique();
            $table->text('description')->nullable();
            $table->string('icon_path', 255)->nullable();
            $table->unsignedBigInteger('default_deposit_amount')->default(0); // paise
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->index('is_active');
        });

        Schema::create('rental_bikes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('rental_bike_categories');
            $table->foreignId('hub_id')->constrained('rental_hubs');
            $table->foreignId('city_id')->constrained('cities');
            $table->string('name', 150);
            $table->string('registration_no', 20)->unique();
            $table->string('iot_device_id', 80)->nullable()->unique();
            $table->string('fuel_type', 20)->default('petrol');
            $table->unsignedSmallInteger('year')->nullable();
            $table->string('color', 40)->nullable();
            $table->json('specifications')->nullable();
            $table->string('status', 20)->default('available');
            $table->unsignedBigInteger('security_deposit_amount')->default(0); // paise
            $table->timestamps();
            $table->softDeletes();
            $table->index('category_id');
            $table->index('hub_id');
            $table->index('status');
            $table->index('city_id');
        });

        Schema::create('rental_bike_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bike_id')->constrained('rental_bikes')->cascadeOnDelete();
            $table->string('image_path', 255);
            $table->boolean('is_primary')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->index(['bike_id', 'is_primary']);
        });

        Schema::create('rental_bike_pricing', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bike_id')->constrained('rental_bikes')->cascadeOnDelete();
            $table->unsignedBigInteger('hourly_rate')->nullable(); // paise/hour
            $table->unsignedBigInteger('daily_rate')->nullable();  // paise/day
            $table->unsignedBigInteger('monthly_rate')->nullable(); // paise/month
            $table->unsignedSmallInteger('min_hours')->default(1);
            $table->date('effective_from')->nullable();
            $table->date('effective_to')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['bike_id', 'is_active']);
        });

        Schema::create('rental_bike_terms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bike_id')->nullable()->constrained('rental_bikes')->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('rental_bike_categories')->cascadeOnDelete();
            $table->text('content');
            $table->integer('version')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rental_bike_terms');
        Schema::dropIfExists('rental_bike_pricing');
        Schema::dropIfExists('rental_bike_images');
        Schema::dropIfExists('rental_bikes');
        Schema::dropIfExists('rental_bike_categories');
        Schema::dropIfExists('rental_hubs');
    }
};
