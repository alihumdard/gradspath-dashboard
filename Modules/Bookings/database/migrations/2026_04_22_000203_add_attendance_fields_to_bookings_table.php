<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->string('attendance_status')->nullable()->after('session_outcome_note');
            $table->timestamp('actual_started_at')->nullable()->after('attendance_status');
            $table->timestamp('actual_ended_at')->nullable()->after('actual_started_at');
            $table->timestamp('host_joined_at')->nullable()->after('actual_ended_at');
            $table->timestamp('first_attendee_joined_at')->nullable()->after('host_joined_at');
            $table->unsignedInteger('attendance_overlap_minutes')->nullable()->after('first_attendee_joined_at');
            $table->timestamp('feedback_unlocked_at')->nullable()->after('feedback_due_at');

            $table->index(['attendance_status', 'feedback_unlocked_at']);
            $table->index('actual_ended_at');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropIndex(['attendance_status', 'feedback_unlocked_at']);
            $table->dropIndex(['actual_ended_at']);

            $table->dropColumn([
                'attendance_status',
                'actual_started_at',
                'actual_ended_at',
                'host_joined_at',
                'first_attendee_joined_at',
                'attendance_overlap_minutes',
                'feedback_unlocked_at',
            ]);
        });
    }
};
