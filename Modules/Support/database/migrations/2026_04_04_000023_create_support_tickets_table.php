<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Auto-generated unique ref e.g. SUP-00102
            $table->string('ticket_ref')->unique();

            $table->string('subject');
            $table->text('message');

            // Sanitized on write — raw stored for audit
            $table->text('message_raw')->nullable();

            $table->enum('status', ['open', 'in_progress', 'resolved', 'closed'])->default('open');

            // Admin response
            $table->text('admin_reply')->nullable();
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('replied_at')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('status');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_tickets');
    }
};
