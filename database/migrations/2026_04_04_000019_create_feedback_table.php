<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feedback', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('mentor_id')->constrained('mentors')->cascadeOnDelete();

            // Student submission
            $table->tinyInteger('stars')->unsigned();                // 1–5
            $table->tinyInteger('preparedness_rating')->unsigned()->nullable(); // mentor preparedness
            $table->text('comment');
            $table->boolean('recommend')->default(true);
            $table->string('service_type')->nullable();              // denormalized for filtering

            // Verification — feedback only submittable after booking status = completed
            $table->boolean('is_verified')->default(true);

            // Admin moderation (Station 3 in Manual Controls)
            // original_comment is immutable — never overwritten, only comment can be amended
            $table->text('original_comment')->nullable();            // populated on first admin amendment
            $table->boolean('is_visible')->default(true);            // false = soft hidden by admin
            $table->text('admin_note')->nullable();
            $table->foreignId('amended_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('amended_at')->nullable();

            // Mentor reply (optional feature)
            $table->text('mentor_reply')->nullable();
            $table->timestamp('replied_at')->nullable();

            $table->timestamps();

            // Indexes for aggregation queries and filtering
            $table->unique(['booking_id', 'student_id']);
            $table->index(['mentor_id', 'is_visible']);
            $table->index(['mentor_id', 'stars']);
            $table->index('created_at');
            $table->index('service_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feedback');
    }
};
