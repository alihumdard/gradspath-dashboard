<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mentor_availability_slots', function (Blueprint $table) {
            $table->dateTime('starts_at_utc')->nullable()->after('timezone')->index();
            $table->dateTime('ends_at_utc')->nullable()->after('starts_at_utc')->index();
        });

        DB::table('mentor_availability_slots')
            ->whereNull('starts_at_utc')
            ->orderBy('id')
            ->each(function (object $slot) {
                $timezone = $slot->timezone ?: config('app.timezone', 'UTC');

                try {
                    $startsAt = Carbon::parse($slot->slot_date.' '.$slot->start_time, $timezone)->utc();
                    $endsAt = Carbon::parse($slot->slot_date.' '.$slot->end_time, $timezone)->utc();
                } catch (Throwable) {
                    return;
                }

                DB::table('mentor_availability_slots')
                    ->where('id', $slot->id)
                    ->update([
                        'starts_at_utc' => $startsAt,
                        'ends_at_utc' => $endsAt,
                    ]);
            });
    }

    public function down(): void
    {
        Schema::table('mentor_availability_slots', function (Blueprint $table) {
            $table->dropColumn(['starts_at_utc', 'ends_at_utc']);
        });
    }
};
