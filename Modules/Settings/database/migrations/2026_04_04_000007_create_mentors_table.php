<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mentors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('university_id')->nullable()->constrained()->nullOnDelete();

            // Identity & display
            $table->string('title')->nullable();          // e.g. "PhD Person", "MBA"
            $table->string('grad_school_display')->nullable(); // short name: "Harvard", "Wharton", "Yale Law"
            $table->enum('mentor_type', ['graduate', 'professional']);
            $table->enum('program_type', ['mba', 'law', 'therapy', 'cmhc', 'mft', 'msw', 'clinical_psy', 'other'])->nullable();

            // Profile content
            $table->text('bio')->nullable();
            $table->text('description')->nullable();      // longer "about" shown on expanded card
            $table->string('office_hours_schedule')->nullable(); // e.g. "Every Tuesday at 5 PM EST"

            // Media — avatar with crop metadata (processed server-side)
            $table->string('avatar_url')->nullable();
            $table->decimal('avatar_crop_zoom', 4, 2)->nullable();
            $table->decimal('avatar_crop_x', 6, 2)->nullable();
            $table->decimal('avatar_crop_y', 6, 2)->nullable();

            // External links
            $table->string('edu_email')->nullable();      // required if mentor_type = graduate
            $table->string('calendly_link')->nullable();
            $table->string('slack_link')->nullable();

            // Aggregate stats are stored in mentor_ratings (single source of truth)
            $table->boolean('is_featured')->default(false); // "Mentors of the Week"

            // Stripe Connect
            $table->string('stripe_account_id')->nullable();
            $table->boolean('payouts_enabled')->default(false);
            $table->boolean('stripe_onboarding_complete')->default(false);

            // Status lifecycle: pending → active / rejected; active ↔ paused
            $table->enum('status', ['pending', 'active', 'paused', 'rejected'])->default('pending');
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            // Indexes for Find Mentors filters and search
            $table->index('mentor_type');
            $table->index('program_type');
            $table->index('is_featured');
            $table->index('status');
            $table->index(['university_id', 'program_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mentors');
    }
};
