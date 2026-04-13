<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mentor_availability_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mentor_id')->constrained('mentors')->cascadeOnDelete();
            $table->foreignId('availability_rule_id')->nullable()->constrained('mentor_availability_rules')->nullOnDelete();
            $table->foreignId('service_config_id')->nullable()->constrained('services_config')->nullOnDelete();
            $table->date('slot_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->string('timezone')->default('America/New_York');
            $table->enum('session_type', ['1on1', '1on3', '1on5'])->default('1on1');
            $table->integer('max_participants')->default(1);
            $table->integer('booked_participants_count')->default(0);
            $table->boolean('is_booked')->default(false);
            $table->boolean('is_blocked')->default(false);
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['mentor_id', 'slot_date', 'start_time', 'session_type'], 'mentor_availability_slots_unique');
            $table->index(['mentor_id', 'slot_date', 'is_active']);
            $table->index(['slot_date', 'session_type', 'is_active']);
            $table->index(['is_booked', 'is_blocked']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mentor_availability_slots');
    }
};
