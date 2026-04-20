<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('office_hour_schedules') || Schema::hasColumn('office_hour_schedules', 'current_service_id')) {
            return;
        }

        Schema::table('office_hour_schedules', function (Blueprint $table) {
            $table->foreignId('current_service_id')
                ->nullable()
                ->after('mentor_id')
                ->constrained('services_config')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('office_hour_schedules') || !Schema::hasColumn('office_hour_schedules', 'current_service_id')) {
            return;
        }

        Schema::table('office_hour_schedules', function (Blueprint $table) {
            $table->dropConstrainedForeignId('current_service_id');
        });
    }
};
