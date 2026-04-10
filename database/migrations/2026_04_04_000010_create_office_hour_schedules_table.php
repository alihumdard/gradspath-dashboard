<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Defines the recurring pattern — e.g. "Every Tuesday at 5 PM EST, weekly"
        Schema::create('office_hour_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mentor_id')->constrained()->cascadeOnDelete();
            $table->enum('day_of_week', ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun']);
            $table->time('start_time');
            $table->string('timezone')->default('America/New_York');
            $table->enum('frequency', ['weekly', 'biweekly'])->default('weekly');
            $table->integer('max_spots')->default(3); // max 3 per session per doc
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['mentor_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('office_hour_schedules');
    }
};
