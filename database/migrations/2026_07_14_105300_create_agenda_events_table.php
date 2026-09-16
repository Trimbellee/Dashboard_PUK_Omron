<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('agenda_events', function (Blueprint $table) {
            $table->id();

            $table->string('title');
            $table->text('description')->nullable();

            $table->date('start_date')->index();
            $table->date('end_date');

            $table->string('location')->nullable();
            $table->time('start_time')->nullable();

            $table->string('category')->default('Deadline');

            $table->string('bidang', 50)->nullable();

            $table->enum('status', [
                'on_track',
                'due_soon',
                'overdue',
                'done',
            ])->default('on_track')->index();

            $table->integer('reminder_offset_days')->default(1);

            $table->string('created_by');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agenda_events');
    }
};