<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Real-time chat messages — available 24–48hrs before session (Laravel Reverb)
        Schema::create('chats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('receiver_id')->constrained('users')->cascadeOnDelete();
            $table->text('message_text');
            $table->boolean('is_read')->default(false);
            $table->timestamp('sent_at')->useCurrent();

            // Indexes for loading chat thread and unread counts
            $table->index(['booking_id', 'sent_at']);
            $table->index(['receiver_id', 'is_read']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chats');
    }
};
