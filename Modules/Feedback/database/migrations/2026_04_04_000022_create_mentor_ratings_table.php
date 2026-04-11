<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Source of truth for mentor aggregate stats — recalculated after every feedback/moderation event
        // Avoids expensive AVG/COUNT on every page load for Find Mentors & Feedback pages
        Schema::create('mentor_ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mentor_id')->unique()->constrained()->cascadeOnDelete();

            $table->decimal('avg_stars', 3, 2)->default(0.00);
            $table->decimal('recommend_rate', 5, 2)->default(0.00);  // percentage e.g. 96.00
            $table->integer('total_reviews')->default(0);
            $table->integer('total_sessions')->default(0);

            // Most mentioned keyword from comment frequency analysis
            $table->string('top_tag')->nullable();                   // e.g. "Clear advice"
            $table->json('top_tags_json')->nullable();               // full list: ["Clear advice","honest","strategic"]

            $table->timestamp('recalculated_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mentor_ratings');
    }
};
