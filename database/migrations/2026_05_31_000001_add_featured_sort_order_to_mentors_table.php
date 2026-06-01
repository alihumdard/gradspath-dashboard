<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mentors', function (Blueprint $table): void {
            $table->unsignedSmallInteger('featured_sort_order')->nullable()->after('is_featured');
            $table->index(['is_featured', 'featured_sort_order']);
        });
    }

    public function down(): void
    {
        Schema::table('mentors', function (Blueprint $table): void {
            $table->dropIndex(['is_featured', 'featured_sort_order']);
            $table->dropColumn('featured_sort_order');
        });
    }
};
