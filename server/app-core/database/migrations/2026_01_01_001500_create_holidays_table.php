<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Public holidays (his order, 2026-11-28): office-managed dates that are
 * non-obligated days for every midwife, like Fridays. The admin adds them
 * in the panel («رخصتی‌های عمومی»); the app calendar shows them by name.
 * Hijri-based holidays move every year, so they are data, not code.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('holidays', function (Blueprint $table) {
            $table->id();
            $table->date('date')->unique();
            $table->string('name', 120);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('holidays');
    }
};
