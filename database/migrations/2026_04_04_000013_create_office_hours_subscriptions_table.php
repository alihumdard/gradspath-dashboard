<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tracks the $200/month Office Hours subscription — separate from one-off credit purchases
        // Each active subscription grants 5 credits per billing cycle via Stripe webhook
        Schema::create('office_hours_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Program chosen at subscribe time (for demand tracking only — credits are universal)
            $table->enum('program', ['mba', 'law', 'therapy']);

            // Stripe recurring subscription
            $table->string('stripe_subscription_id')->unique();
            $table->string('stripe_customer_id')->nullable();

            // Credits granted per cycle
            $table->integer('credits_per_cycle')->default(5);

            // Billing period (populated/updated on every Stripe webhook)
            $table->timestamp('current_period_start')->nullable();
            $table->timestamp('current_period_end')->nullable();

            $table->enum('status', ['active', 'cancelled', 'past_due', 'incomplete'])->default('active');
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('stripe_subscription_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('office_hours_subscriptions');
    }
};
