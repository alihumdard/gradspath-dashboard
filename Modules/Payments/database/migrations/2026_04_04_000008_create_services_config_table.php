<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Global service catalogue managed by admin (Station 5 & 6 in Manual Controls)
        // Prices here are the source of truth — synced across the entire platform
        Schema::create('services_config', function (Blueprint $table) {
            $table->id();
            $table->string('service_name');               // "Program Insights", "Interview Prep"
            $table->string('service_slug')->unique();     // "program_insights", "interview_prep"
            $table->integer('duration_minutes')->default(60);
            $table->boolean('is_active')->default(true);

            // Pricing per meeting size (null = size not available for this service)
            $table->decimal('price_1on1', 8, 2)->nullable();
            $table->decimal('price_1on3_per_person', 8, 2)->nullable();
            $table->decimal('price_1on3_total', 8, 2)->nullable();
            $table->decimal('price_1on5_per_person', 8, 2)->nullable();
            $table->decimal('price_1on5_total', 8, 2)->nullable();

            // Office Hours is a special subscription-based service
            $table->boolean('is_office_hours')->default(false);
            $table->decimal('office_hours_subscription_price', 8, 2)->nullable(); // $200/month

            // Credit costs (used for deduction logic)
            $table->integer('credit_cost_1on1')->default(1);
            $table->integer('credit_cost_1on3')->default(1);
            $table->integer('credit_cost_1on5')->default(1);

            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index('is_active');
            $table->index('sort_order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('services_config');
    }
};
