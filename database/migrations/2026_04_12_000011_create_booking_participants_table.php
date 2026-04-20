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
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('full_name')->nullable();
            $table->string('email')->nullable();
            $table->string('participant_role')->default('student');
            $table->boolean('is_primary')->default(false);
            $table->enum('invite_status', ['pending', 'accepted', 'declined'])->default('pending');
            $table->timestamps();

            $table->unique(['booking_id', 'user_id']);
            $table->unique(['booking_id', 'email']);
            $table->index(['user_id', 'participant_role']);
            $table->index(['email', 'invite_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_participants');
    }
};
