<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mentor_ratings', function (Blueprint $table) {
            $table->decimal('admin_rating_override', 3, 2)->nullable()->after('avg_stars');
            $table->string('admin_rating_override_note', 1000)->nullable()->after('admin_rating_override');
            $table->foreignId('admin_rating_overridden_by')->nullable()->after('admin_rating_override_note')->constrained('users')->nullOnDelete();
            $table->timestamp('admin_rating_overridden_at')->nullable()->after('admin_rating_overridden_by');
        });
    }

    public function down(): void
    {
        Schema::table('mentor_ratings', function (Blueprint $table) {
            $table->dropForeign(['admin_rating_overridden_by']);
            $table->dropColumn([
                'admin_rating_override',
                'admin_rating_override_note',
                'admin_rating_overridden_by',
                'admin_rating_overridden_at',
            ]);
        });
    }
};
