<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('monthly_work_reports', function (Blueprint $table) {
            $table->string('photo_opv')->nullable();
            $table->string('photo_opd')->nullable();
            $table->string('photo_fp')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('monthly_work_reports', function (Blueprint $table) {
            $table->dropColumn(['photo_opv', 'photo_opd', 'photo_fp']);
        });
    }
};
