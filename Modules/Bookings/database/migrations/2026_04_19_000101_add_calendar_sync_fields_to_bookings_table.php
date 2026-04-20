<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->string('external_calendar_event_id')->nullable()->after('meeting_type');
            $table->string('calendar_provider', 40)->nullable()->after('external_calendar_event_id');
            $table->string('calendar_sync_status', 40)->default('not_synced')->after('calendar_provider');
            $table->text('calendar_last_error')->nullable()->after('calendar_sync_status');

            $table->index(['calendar_provider', 'calendar_sync_status']);
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropIndex(['calendar_provider', 'calendar_sync_status']);
            $table->dropColumn([
                'external_calendar_event_id',
                'calendar_provider',
                'calendar_sync_status',
                'calendar_last_error',
            ]);
        });
    }
};
