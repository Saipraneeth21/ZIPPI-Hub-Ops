<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rental_payments', function (Blueprint $table) {
            $table->id();
            $table->string('payment_reference', 40)->unique();
            $table->foreignId('booking_id')->constrained('rental_bookings');
            $table->foreignId('user_id')->constrained('users');
            $table->string('gateway', 20)->default('razorpay');
            $table->string('gateway_order_id', 80)->nullable();
            $table->string('gateway_payment_id', 80)->nullable();
            $table->string('gateway_signature', 255)->nullable();
            $table->unsignedBigInteger('amount'); // paise
            $table->string('currency', 3)->default('INR');
            $table->string('method', 20)->nullable();
            $table->string('status', 20)->default('created');
            $table->string('idempotency_key', 64)->unique();
            $table->string('failure_reason', 255)->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
            $table->index('gateway_order_id');
            $table->index('gateway_payment_id');
            $table->index('booking_id');
            $table->index('status');
        });

        Schema::create('rental_wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->bigInteger('balance')->default(0); // paise
            $table->string('currency', 3)->default('INR');
            $table->timestamps();
        });

        Schema::create('rental_wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')->constrained('rental_wallets');
            $table->foreignId('user_id')->constrained('users');
            $table->string('direction', 6); // credit|debit
            $table->unsignedBigInteger('amount'); // paise
            $table->bigInteger('balance_after'); // paise
            $table->string('source_type', 30);
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('description', 255);
            $table->timestamps();
            $table->index(['wallet_id', 'created_at']);
            $table->index('user_id');
            $table->index(['source_type', 'source_id']);
        });

        Schema::create('rental_refunds', function (Blueprint $table) {
            $table->id();
            $table->string('refund_reference', 40)->unique();
            $table->foreignId('booking_id')->constrained('rental_bookings');
            $table->foreignId('payment_id')->nullable()->constrained('rental_payments')->nullOnDelete();
            $table->foreignId('user_id')->constrained('users');
            $table->string('type', 20); // deposit|cancellation|partial
            $table->unsignedBigInteger('amount'); // paise
            $table->string('destination', 10)->default('wallet'); // wallet|source
            $table->unsignedBigInteger('deductions')->default(0);
            $table->string('deduction_note', 255)->nullable();
            $table->string('gateway_refund_id', 80)->nullable();
            $table->string('status', 20)->default('pending');
            $table->string('idempotency_key', 64)->unique();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
            $table->index('booking_id');
            $table->index('user_id');
            $table->index('status');
        });

        Schema::create('rental_coupon_redemptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coupon_id')->constrained('rental_coupons')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('booking_id')->nullable()->constrained('rental_bookings')->nullOnDelete();
            $table->unsignedBigInteger('discount_amount');
            $table->timestamps();
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rental_coupon_redemptions');
        Schema::dropIfExists('rental_refunds');
        Schema::dropIfExists('rental_wallet_transactions');
        Schema::dropIfExists('rental_wallets');
        Schema::dropIfExists('rental_payments');
    }
};
