<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Monthly Work Report feature (ARCS plan, approved 2026-11-27):
     * one monthly work report per midwife per month (vaccination/OPV,
     * OPD, family planning, maternal & nutrition, stock), plus optional
     * daily quick-entry counters that prefill the monthly form.
     */
    public function up(): void
    {
        Schema::create('monthly_work_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('midwife_id')->constrained()->cascadeOnDelete();
            $table->char('month', 7); // Y-m, Asia/Kabul
            $table->string('status', 16)->default('draft'); // draft|submitted|returned|approved
            $table->json('payload')->nullable();
            $table->json('totals')->nullable();
            $table->text('supervisor_note')->nullable();
            $table->string('register_photo')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamps();
            $table->unique(['midwife_id', 'month']);
        });

        Schema::create('work_report_daily_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('midwife_id')->constrained()->cascadeOnDelete();
            $table->date('entry_date');
            $table->json('payload')->nullable();
            $table->timestamps();
            $table->unique(['midwife_id', 'entry_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_report_daily_entries');
        Schema::dropIfExists('monthly_work_reports');
    }
};
