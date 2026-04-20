<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Immutable ledger — every credit change writes a row here. Never update, only insert.
        Schema::create('credit_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // nullable: some transactions (admin manual) are not tied to a booking
            $table->foreignId('booking_id')->nullable()->constrained()->nullOnDelete();

            // nullable: some transactions (admin manual) are not tied to a subscription
            $table->foreignId('subscription_id')->nullable()->constrained('office_hours_subscriptions')->nullOnDelete();

            // Transaction type
            $table->enum('type', ['purchase', 'subscription', 'deduction', 'refund', 'manual']);

            // Positive = credits added, negative = credits deducted
            $table->integer('amount');

            // Balance snapshot after this transaction (for audit/debugging)
            $table->integer('balance_after');

            // Stripe references
            $table->string('stripe_payment_id')->nullable();
            $table->string('stripe_event_id')->nullable();
            $table->string('stripe_subscription_id')->nullable();

            // For subscription credits: which program
            $table->enum('office_hours_program', ['mba', 'law', 'therapy'])->nullable();

            // Human-readable description for admin audit trail
            $table->string('description')->nullable();

            // Manual transactions: who did it
            $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamp('created_at')->useCurrent();

            // Indexes for financial reporting and per-user history
            $table->index(['user_id', 'created_at']);
            $table->index('type');
            $table->index('stripe_payment_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_transactions');
    }
};
