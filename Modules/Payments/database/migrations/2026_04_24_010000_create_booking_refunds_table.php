<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_refunds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();
            $table->foreignId('booking_payment_id')->nullable()->constrained('booking_payments')->nullOnDelete();
            $table->foreignId('mentor_payout_id')->nullable()->constrained('mentor_payouts')->nullOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->enum('type', ['credits', 'stripe']);
            $table->enum('status', ['pending', 'succeeded', 'failed', 'requires_admin_review'])->default('pending');
            $table->decimal('amount', 10, 2)->default(0);
            $table->unsignedInteger('credits')->default(0);
            $table->string('currency', 3)->default('USD');
            $table->string('stripe_refund_id')->nullable()->unique();
            $table->string('stripe_transfer_reversal_id')->nullable()->unique();
            $table->text('failure_reason')->nullable();
            $table->timestamp('requested_at')->nullable();
            $table->timestamp('succeeded_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();

            $table->unique('booking_id');
            $table->index(['status', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_refunds');
    }
};
