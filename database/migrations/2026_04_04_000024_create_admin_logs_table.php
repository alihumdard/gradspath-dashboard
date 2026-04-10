<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Immutable audit log — every admin manual action writes a row here (who, when, what)
        // Covers all 6 Manual Action stations
        Schema::create('admin_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->constrained('users')->cascadeOnDelete();

            // What station/action was performed
            $table->string('action');           // e.g. "amend_mentor", "manual_refund", "delete_feedback"
            $table->string('target_table');     // e.g. "mentors", "feedback", "user_credits"
            $table->unsignedBigInteger('target_id')->nullable();

            // State snapshots for full auditability
            $table->json('before_state')->nullable();
            $table->json('after_state')->nullable();

            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->text('notes')->nullable();  // optional admin comment on why action was taken

            $table->timestamp('created_at')->useCurrent();

            $table->index(['admin_id', 'created_at']);
            $table->index(['target_table', 'target_id']);
            $table->index('action');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_logs');
    }
};
