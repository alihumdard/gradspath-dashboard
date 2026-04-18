<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('mentor_id')->constrained('mentors')->cascadeOnDelete();
            $table->foreignId('service_config_id')->constrained('services_config');
            $table->foreignId('booking_id')->nullable()->constrained('bookings')->nullOnDelete();
            $table->foreignId('mentor_availability_slot_id')->nullable()->constrained('mentor_availability_slots')->nullOnDelete();
            $table->foreignId('office_hour_session_id')->nullable()->constrained('office_hour_sessions')->nullOnDelete();
            $table->enum('session_type', ['1on1', '1on3', '1on5', 'office_hours'])->default('1on1');
            $table->enum('meeting_type', ['zoom', 'google_meet'])->default('zoom');
            $table->decimal('amount', 8, 2)->default(0);
            $table->string('currency', 3)->default('USD');
            $table->json('guest_participants')->nullable();
            $table->json('request_payload');
            $table->string('stripe_checkout_session_id')->nullable()->unique();
            $table->string('stripe_payment_intent_id')->nullable()->index();
            $table->string('stripe_event_id')->nullable()->index();
            $table->text('checkout_url')->nullable();
            $table->enum('status', ['initiated', 'paid', 'booking_created', 'failed', 'cancelled'])->default('initiated');
            $table->text('failure_reason')->nullable();
            $table->timestamp('payment_completed_at')->nullable();
            $table->timestamp('booking_created_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['booking_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_payments');
    }
};
