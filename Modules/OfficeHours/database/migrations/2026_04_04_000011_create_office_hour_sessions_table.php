<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Each actual session generated from a schedule by the CRON rotation engine
        Schema::create('office_hour_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('schedule_id')->constrained('office_hour_schedules')->cascadeOnDelete();

            // Current rotated service for this session
            $table->foreignId('current_service_id')->constrained('services_config');

            // Session timing
            $table->date('session_date');
            $table->time('start_time');
            $table->string('timezone')->default('America/New_York');

            // Spot tracking (core of the booking logic)
            $table->integer('current_occupancy')->default(0);
            $table->integer('max_spots')->default(3);
            $table->boolean('is_full')->default(false);

            // First-student-choice logic
            // Once a 2nd student books, service_locked = true and cannot change
            $table->boolean('service_locked')->default(false);
            $table->foreignId('first_booker_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('first_booked_at')->nullable();

            // 24-hour cutoff: first student can only choose service if booked >= 24hrs before session
            $table->timestamp('service_choice_cutoff_at')->nullable();

            $table->enum('status', ['upcoming', 'in_progress', 'completed', 'cancelled'])->default('upcoming');
            $table->timestamps();

            // Critical indexes for real-time spot display
            $table->index(['schedule_id', 'session_date']);
            $table->index(['status', 'session_date']);
            $table->index('is_full');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('office_hour_sessions');
    }
};
