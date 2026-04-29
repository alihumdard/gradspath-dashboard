<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('office_hour_sessions', function (Blueprint $table) {
            $table->string('meeting_link', 2000)->nullable()->after('service_choice_cutoff_at');
            $table->string('external_calendar_event_id', 191)->nullable()->after('meeting_link');
            $table->string('calendar_provider', 50)->nullable()->after('external_calendar_event_id');
            $table->string('calendar_sync_status', 30)->default('not_synced')->after('calendar_provider');
            $table->text('calendar_last_error')->nullable()->after('calendar_sync_status');

            $table->index(['calendar_provider', 'external_calendar_event_id'], 'office_hour_sessions_calendar_lookup_idx');
        });
    }

    public function down(): void
    {
        Schema::table('office_hour_sessions', function (Blueprint $table) {
            $table->dropIndex('office_hour_sessions_calendar_lookup_idx');
            $table->dropColumn([
                'meeting_link',
                'external_calendar_event_id',
                'calendar_provider',
                'calendar_sync_status',
                'calendar_last_error',
            ]);
        });
    }
};
