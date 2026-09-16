<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('agenda_events', function (Blueprint $table) {
            $table->boolean('reminder_sent')->default(false)->after('reminder_offset_days');
        });
    }

    public function down()
    {
        Schema::table('agenda_events', function (Blueprint $table) {
            $table->dropColumn('reminder_sent');
        });
    }
};