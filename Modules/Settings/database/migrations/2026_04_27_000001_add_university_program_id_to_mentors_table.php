<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mentors', function (Blueprint $table) {
            $table->foreignId('university_program_id')
                ->nullable()
                ->after('university_id')
                ->constrained('university_programs')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('mentors', function (Blueprint $table) {
            $table->dropConstrainedForeignId('university_program_id');
        });
    }
};
