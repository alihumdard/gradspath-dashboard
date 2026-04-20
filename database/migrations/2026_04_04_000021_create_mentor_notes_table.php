<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Highly sensitive — mentor-only, zero student access
        // 5-question structured form per session (as seen in demo8.html)
        Schema::create('mentor_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mentor_id')->constrained('mentors')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();

            // Links to the specific session this note is about (optional but recommended)
            $table->foreignId('booking_id')->nullable()->constrained()->nullOnDelete();

            $table->date('session_date');
            $table->string('service_type')->nullable();  // denormalized for display

            // 5 structured questions from the template (demo8.html)
            $table->text('worked_on')->nullable();              // Q1: What did you work on during this session?
            $table->text('next_steps')->nullable();             // Q2: What should happen next, and what does the user need most?
            $table->text('session_result')->nullable();         // Q3: What was the result of the session?
            $table->text('strengths_challenges')->nullable();   // Q4: One strength and one challenge from the session?
            $table->text('other_notes')->nullable();            // Q5: Any other notes to share?

            // Soft delete — admin only, record stays in DB for backup
            $table->boolean('is_deleted')->default(false);
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('deleted_at')->nullable();

            $table->timestamps();

            // Critical indexes for search (ILIKE on student name requires join — index student_id and mentor_id)
            $table->index(['mentor_id', 'student_id']);
            $table->index(['mentor_id', 'is_deleted']);
            $table->index('session_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mentor_notes');
    }
};
