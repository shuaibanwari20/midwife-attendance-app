<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Feature 49 — one monthly-survey response per midwife per month. */
    public function up(): void
    {
        Schema::create('survey_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('midwife_id')->constrained()->cascadeOnDelete();
            $table->char('month', 7); // Y-m, Asia/Kabul
            $table->unsignedTinyInteger('satisfaction'); // 1..5
            $table->json('problems')->nullable();
            $table->text('comment')->nullable();
            $table->timestamps();
            $table->unique(['midwife_id', 'month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('survey_responses');
    }
};
