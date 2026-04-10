<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('universities', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('display_name')->nullable(); // short version e.g. "Harvard", "Yale Law"
            $table->string('country')->default('US');
            $table->string('logo_url')->nullable();
            $table->enum('tier', ['elite', 'top25', 'regional']);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Indexes for filtering and search (used heavily in Institutions module)
            $table->index('tier');
            $table->index('is_active');
            $table->index('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('universities');
    }
};
