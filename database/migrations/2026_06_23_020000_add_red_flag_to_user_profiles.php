<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rental_user_profiles', function (Blueprint $table) {
            // Red flag: a warning marker for users with repeated document/KYC issues.
            // Distinct from is_blocked — flagging is a softer "watch this user" signal.
            $table->boolean('is_red_flagged')->default(false)->after('blocked_reason');
            $table->string('red_flag_reason', 255)->nullable()->after('is_red_flagged');
            $table->timestamp('red_flagged_at')->nullable()->after('red_flag_reason');

            $table->index('is_red_flagged');
        });
    }

    public function down(): void
    {
        Schema::table('rental_user_profiles', function (Blueprint $table) {
            $table->dropIndex(['is_red_flagged']);
            $table->dropColumn(['is_red_flagged', 'red_flag_reason', 'red_flagged_at']);
        });
    }
};
