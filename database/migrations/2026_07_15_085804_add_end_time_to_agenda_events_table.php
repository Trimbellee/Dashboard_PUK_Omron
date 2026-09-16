<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('agenda_events', function (Blueprint $table) {
            $table->time('end_time')->nullable()->after('start_time');
        });
    }

    public function down()
    {
        Schema::table('agenda_events', function (Blueprint $table) {
            $table->dropColumn('end_time');
        });
    }
};