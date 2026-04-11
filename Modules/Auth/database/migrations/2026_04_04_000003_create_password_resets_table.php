<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Better password resets table — replaces Laravel's default password_reset_tokens.
     * Uses proper id PK, unique token column, and an explicit expires_at timestamp.
     */
    public function up(): void
    {
        Schema::create('password_resets', function (Blueprint $table) {
            $table->id();
            $table->string('email')->index();      // not a FK — allows reset even if email changes
            $table->string('token')->unique();     // cryptographically secure token
            $table->timestamp('expires_at');       // 24-hour expiry — enforced in AuthController
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('password_resets');
    }
};
