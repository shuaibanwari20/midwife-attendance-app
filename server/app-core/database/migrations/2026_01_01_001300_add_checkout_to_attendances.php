<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Two-time attendance (2026-11-28): the midwife also records LEAVING work
 * (check-out) inside an admin-set window. The check-out lives on the same
 * attendance row as the morning check-in.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dateTime('checkout_at')->nullable();
            $table->double('checkout_lat')->nullable();
            $table->double('checkout_lng')->nullable();
            $table->double('checkout_distance_m')->nullable();
            $table->double('checkout_gps_accuracy_m')->nullable();
            $table->boolean('checkout_offline')->default(false);
            $table->string('checkout_client_uuid', 64)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn([
                'checkout_at', 'checkout_lat', 'checkout_lng', 'checkout_distance_m',
                'checkout_gps_accuracy_m', 'checkout_offline', 'checkout_client_uuid',
            ]);
        });
    }
};
