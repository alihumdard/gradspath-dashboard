<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('university_id')->nullable()->constrained('universities')->nullOnDelete();
            $table->string('institution_text')->nullable();
            $table->enum('program_level', ['undergrad', 'grad', 'professional'])->nullable();
            $table->enum('program_type', ['mba', 'law', 'therapy', 'cmhc', 'mft', 'msw', 'clinical_psy', 'other'])->nullable();
            $table->timestamps();

            $table->index(['university_id', 'program_level']);
            $table->index(['university_id', 'program_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_profiles');
    }
};
