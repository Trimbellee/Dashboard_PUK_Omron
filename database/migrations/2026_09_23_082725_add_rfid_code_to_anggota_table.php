<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('anggota', function (Blueprint $table) {
            $table->string('rfid_code', 50)->nullable()->unique()->after('ulid');
        });
    }

    public function down()
    {
        Schema::table('anggota', function (Blueprint $table) {
            $table->dropColumn('rfid_code');
        });
    }
};
