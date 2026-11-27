<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('monthly_work_reports', function (Blueprint $table) {
            $table->string('review_opv')->nullable();
            $table->string('review_opd')->nullable();
            $table->string('review_fp')->nullable();
            $table->text('note_opv')->nullable();
            $table->text('note_opd')->nullable();
            $table->text('note_fp')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('monthly_work_reports', function (Blueprint $table) {
            $table->dropColumn(['review_opv', 'review_opd', 'review_fp', 'note_opv', 'note_opd', 'note_fp']);
        });
    }
};
