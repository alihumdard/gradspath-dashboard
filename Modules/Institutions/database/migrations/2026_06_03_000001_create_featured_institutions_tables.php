<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('featured_institution_settings', function (Blueprint $table) {
            $table->id();
            $table->string('mode')->default('automatic');
            $table->timestamp('last_recalculated_at')->nullable();
            $table->timestamps();

            $table->index('mode');
        });

        Schema::create('featured_institutions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('university_id')->constrained('universities')->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->unsignedInteger('meetings_count')->default(0);
            $table->string('source')->default('automatic');
            $table->timestamps();

            $table->unique(['source', 'university_id']);
            $table->index(['source', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('featured_institutions');
        Schema::dropIfExists('featured_institution_settings');
    }
};
