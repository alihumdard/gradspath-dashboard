<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Raw Stripe event log — prevents duplicate processing if webhook fires twice
        Schema::create('stripe_webhooks', function (Blueprint $table) {
            $table->id();
            $table->string('event_id')->unique();    // Stripe's evt_xxx ID — unique constraint is the guard
            $table->string('event_type');            // e.g. checkout.session.completed
            $table->json('payload');                 // full raw payload for debugging/replay
            $table->boolean('processed')->default(false);
            $table->string('error_message')->nullable(); // populated if processing failed
            $table->timestamp('received_at')->useCurrent();
            $table->timestamp('processed_at')->nullable();

            $table->index('event_id');
            $table->index(['processed', 'received_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stripe_webhooks');
    }
};
