<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_meeting_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->nullable()->constrained('bookings')->nullOnDelete();
            $table->string('provider', 40);
            $table->string('provider_meeting_id', 191)->nullable();
            $table->string('event_id')->nullable();
            $table->string('event_type', 120);
            $table->timestamp('occurred_at')->nullable();
            $table->timestamp('received_at');
            $table->timestamp('meeting_started_at')->nullable();
            $table->timestamp('meeting_ended_at')->nullable();
            $table->timestamp('host_joined_at')->nullable();
            $table->timestamp('first_participant_joined_at')->nullable();
            $table->boolean('is_verified')->default(false);
            $table->boolean('processed')->default(false);
            $table->string('payload_hash', 64)->unique();
            $table->json('payload');
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['provider', 'provider_meeting_id']);
            $table->index(['booking_id', 'event_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_meeting_events');
    }
};
