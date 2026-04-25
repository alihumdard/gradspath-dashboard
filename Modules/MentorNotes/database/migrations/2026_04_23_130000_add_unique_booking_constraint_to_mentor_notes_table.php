<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mentor_notes', function (Blueprint $table) {
            $table->unique(['mentor_id', 'booking_id'], 'mentor_notes_mentor_booking_unique');
        });
    }

    public function down(): void
    {
        Schema::table('mentor_notes', function (Blueprint $table) {
            $table->dropUnique('mentor_notes_mentor_booking_unique');
        });
    }
};
