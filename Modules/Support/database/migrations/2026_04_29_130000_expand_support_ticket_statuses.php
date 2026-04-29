<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('support_tickets')) {
            return;
        }

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE support_tickets MODIFY status ENUM('open', 'pending', 'in_progress', 'more_information_required', 'resolved', 'closed') NOT NULL DEFAULT 'open'");
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('support_tickets')) {
            return;
        }

        if (DB::getDriverName() === 'mysql') {
            DB::table('support_tickets')
                ->whereIn('status', ['pending', 'more_information_required'])
                ->update(['status' => 'in_progress']);

            DB::statement("ALTER TABLE support_tickets MODIFY status ENUM('open', 'in_progress', 'resolved', 'closed') NOT NULL DEFAULT 'open'");
        }
    }
};
