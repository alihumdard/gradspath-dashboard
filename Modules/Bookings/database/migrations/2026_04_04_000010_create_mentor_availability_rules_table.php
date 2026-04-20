<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mentor_availability_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mentor_id')->constrained('mentors')->cascadeOnDelete();
            $table->enum('day_of_week', ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun']);
            $table->time('start_time');
            $table->time('end_time');
            $table->string('timezone')->default('America/New_York');
            $table->integer('slot_duration_minutes')->default(60);
            $table->enum('session_type', ['1on1', '1on3', '1on5'])->default('1on1');
            $table->foreignId('service_config_id')->nullable()->constrained('services_config')->nullOnDelete();
            $table->integer('max_participants')->default(1);
            $table->enum('frequency', ['weekly', 'biweekly'])->default('weekly');
            $table->date('effective_from')->nullable();
            $table->date('effective_until')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['mentor_id', 'is_active']);
            $table->index(['day_of_week', 'is_active']);
            $table->index(['session_type', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mentor_availability_rules');
    }
};
