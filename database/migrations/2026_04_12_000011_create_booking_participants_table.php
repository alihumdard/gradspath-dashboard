<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('participant_role')->default('student');
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->unique(['booking_id', 'user_id']);
            $table->index(['user_id', 'participant_role']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_participants');
    }
};
