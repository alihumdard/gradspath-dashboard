<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Mentor's mandatory post-session form about the student
        // Separate from student feedback — both are required within 24hrs
        Schema::create('mentor_feedback', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->foreignId('mentor_id')->constrained('mentors')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();

            $table->tinyInteger('engagement_score')->unsigned()->nullable(); // 1–5
            $table->text('notes')->nullable();                               // general notes
            $table->timestamps();

            $table->unique(['booking_id', 'mentor_id']); // one form per mentor per session
            $table->index(['mentor_id', 'student_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mentor_feedback');
    }
};
