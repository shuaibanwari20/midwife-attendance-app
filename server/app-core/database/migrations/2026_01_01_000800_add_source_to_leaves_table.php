<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leaves', function (Blueprint $table) {
            // request = the midwife asked (app) and the admin approved —
            //          consumes her personal leave balance.
            // office  = official leave granted directly by the office/admin
            //          (رخصتی رسمی دفتر), for one midwife or all of them —
            //          does NOT consume personal balance.
            $table->string('source', 20)->default('request');
        });
    }

    public function down(): void
    {
        Schema::table('leaves', function (Blueprint $table) {
            $table->dropColumn('source');
        });
    }
};
