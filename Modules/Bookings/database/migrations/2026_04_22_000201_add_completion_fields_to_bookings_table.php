<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->timestamp('completed_at')->nullable()->after('cancelled_by');
            $table->string('completion_source', 40)->nullable()->after('completed_at');
            $table->string('session_outcome', 40)->nullable()->after('completion_source');
            $table->text('session_outcome_note')->nullable()->after('session_outcome');

            $table->index(['completed_at', 'completion_source']);
            $table->index('session_outcome');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropIndex(['completed_at', 'completion_source']);
            $table->dropIndex(['session_outcome']);
            $table->dropColumn([
                'completed_at',
                'completion_source',
                'session_outcome',
                'session_outcome_note',
            ]);
        });
    }
};
