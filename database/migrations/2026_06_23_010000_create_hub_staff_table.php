<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hub_staff', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hub_id')->nullable()->constrained('rental_hubs')->nullOnDelete();
            $table->string('name', 150);
            $table->string('role', 50);
            $table->string('employee_code', 50)->unique();
            $table->string('password', 255);
            $table->string('photo_path')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('hub_id');
            $table->index('role');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hub_staff');
    }
};
