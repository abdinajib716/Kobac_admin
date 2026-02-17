<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adds support for offline/manual payment processing
     */
    public function up(): void
    {
        // Update subscriptions table - add pending_payment status
        Schema::table('subscriptions', function (Blueprint $table) {
            // Change status enum to include 'pending_payment'
            // We'll do this with raw SQL for MySQL enum modification
        });
        
        // Modify the enum to include pending_payment
        DB::statement("ALTER TABLE subscriptions MODIFY COLUMN status ENUM('trial', 'active', 'expired', 'cancelled', 'pending_payment') DEFAULT 'trial'");

        // Update payment_transactions table
        Schema::table('payment_transactions', function (Blueprint $table) {
            // Add offline payment specific fields
            $table->string('payment_type')->default('online')->after('payment_method')
                ->comment('online (WaafiPay) or offline (manual)');
            $table->foreignId('subscription_id')->nullable()->after('invoice_id')
                ->constrained('subscriptions')->onDelete('set null');
            $table->foreignId('plan_id')->nullable()->after('subscription_id')
                ->constrained('plans')->onDelete('set null');
            $table->foreignId('approved_by')->nullable()->after('user_agent')
                ->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable()->after('approved_by');
            $table->text('rejection_reason')->nullable()->after('approved_at');
            $table->text('admin_notes')->nullable()->after('rejection_reason');
            $table->string('proof_of_payment')->nullable()->after('admin_notes')
                ->comment('File path for payment proof upload');
        });

        // Update status enum to include pending_approval and approved
        DB::statement("ALTER TABLE payment_transactions MODIFY COLUMN status ENUM('pending', 'pending_approval', 'processing', 'success', 'approved', 'failed', 'cancelled', 'refunded', 'rejected') DEFAULT 'pending'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment_transactions', function (Blueprint $table) {
            $table->dropForeign(['subscription_id']);
            $table->dropForeign(['plan_id']);
            $table->dropForeign(['approved_by']);
            $table->dropColumn([
                'payment_type',
                'subscription_id',
                'plan_id',
                'approved_by',
                'approved_at',
                'rejection_reason',
                'admin_notes',
                'proof_of_payment',
            ]);
        });

        // Revert status enum
        DB::statement("ALTER TABLE payment_transactions MODIFY COLUMN status ENUM('pending', 'processing', 'success', 'failed', 'cancelled', 'refunded') DEFAULT 'pending'");
        DB::statement("ALTER TABLE subscriptions MODIFY COLUMN status ENUM('trial', 'active', 'expired', 'cancelled') DEFAULT 'trial'");
    }
};
