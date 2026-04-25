<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mentor_payouts', function (Blueprint $table) {
            $table->foreignId('booking_id')->nullable()->after('mentor_id')->constrained('bookings')->nullOnDelete();
            $table->foreignId('booking_payment_id')->nullable()->after('booking_id')->constrained('booking_payments')->nullOnDelete();
            $table->foreignId('student_id')->nullable()->after('booking_payment_id')->constrained('users')->nullOnDelete();
            $table->string('stripe_account_id')->nullable()->after('student_id');
            $table->decimal('gross_amount', 10, 2)->default(0)->after('amount');
            $table->decimal('mentor_share_amount', 10, 2)->default(0)->after('gross_amount');
            $table->decimal('platform_fee_amount', 10, 2)->default(0)->after('mentor_share_amount');
            $table->string('currency', 3)->default('USD')->after('platform_fee_amount');
            $table->json('calculation_rule')->nullable()->after('currency');
            $table->string('stripe_balance_transaction_id')->nullable()->after('stripe_transfer_id');
            $table->timestamp('eligible_at')->nullable()->after('payout_date');
            $table->timestamp('transferred_at')->nullable()->after('eligible_at');
            $table->timestamp('paid_out_at')->nullable()->after('transferred_at');
            $table->timestamp('failed_at')->nullable()->after('paid_out_at');
            $table->unsignedInteger('attempt_count')->default(0)->after('failed_at');
            $table->timestamp('last_attempt_at')->nullable()->after('attempt_count');

            $table->unique('booking_id');
            $table->index(['status', 'eligible_at']);
        });

        DB::statement("
            ALTER TABLE mentor_payouts
            MODIFY status ENUM('pending', 'paid', 'failed', 'pending_release', 'ready', 'transferred', 'paid_out', 'reversed')
            NOT NULL DEFAULT 'pending_release'
        ");

        DB::table('mentor_payouts')
            ->orderBy('id')
            ->get()
            ->each(function (object $payout): void {
                $status = match ((string) $payout->status) {
                    'paid' => 'paid_out',
                    'pending' => 'ready',
                    'failed' => 'failed',
                    default => 'pending_release',
                };

                DB::table('mentor_payouts')
                    ->where('id', $payout->id)
                    ->update([
                        'status' => $status,
                        'gross_amount' => (float) ($payout->amount ?? 0),
                        'mentor_share_amount' => (float) ($payout->amount ?? 0),
                        'platform_fee_amount' => 0,
                        'currency' => 'USD',
                        'eligible_at' => $status === 'ready' ? ($payout->created_at ?? now()) : null,
                        'transferred_at' => $status === 'paid_out' ? ($payout->payout_date ?? $payout->created_at ?? now()) : null,
                        'paid_out_at' => $status === 'paid_out' ? ($payout->payout_date ?? $payout->created_at ?? now()) : null,
                        'failed_at' => $status === 'failed' ? ($payout->updated_at ?? $payout->created_at ?? now()) : null,
                    ]);
            });

        DB::statement("
            ALTER TABLE mentor_payouts
            MODIFY status ENUM('pending_release', 'ready', 'transferred', 'paid_out', 'failed', 'reversed')
            NOT NULL DEFAULT 'pending_release'
        ");
    }

    public function down(): void
    {
        DB::statement("
            ALTER TABLE mentor_payouts
            MODIFY status ENUM('pending', 'paid', 'failed', 'pending_release', 'ready', 'transferred', 'paid_out', 'reversed')
            NOT NULL DEFAULT 'pending'
        ");

        DB::table('mentor_payouts')
            ->orderBy('id')
            ->get()
            ->each(function (object $payout): void {
                $status = match ((string) $payout->status) {
                    'transferred', 'paid_out' => 'paid',
                    'failed' => 'failed',
                    default => 'pending',
                };

                DB::table('mentor_payouts')
                    ->where('id', $payout->id)
                    ->update([
                        'status' => $status,
                    ]);
            });

        DB::statement("
            ALTER TABLE mentor_payouts
            MODIFY status ENUM('pending', 'paid', 'failed')
            NOT NULL DEFAULT 'pending'
        ");

        Schema::table('mentor_payouts', function (Blueprint $table) {
            $table->dropUnique(['booking_id']);
            $table->dropIndex(['status', 'eligible_at']);
            $table->dropConstrainedForeignId('booking_id');
            $table->dropConstrainedForeignId('booking_payment_id');
            $table->dropConstrainedForeignId('student_id');
            $table->dropColumn([
                'stripe_account_id',
                'gross_amount',
                'mentor_share_amount',
                'platform_fee_amount',
                'currency',
                'calculation_rule',
                'stripe_balance_transaction_id',
                'eligible_at',
                'transferred_at',
                'paid_out_at',
                'failed_at',
                'attempt_count',
                'last_attempt_at',
            ]);
        });
    }
};
