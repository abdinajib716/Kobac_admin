<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Ensure plans.billing_cycle supports all cycles used by admin UI.
     */
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement(
            "ALTER TABLE plans MODIFY billing_cycle ENUM('weekly','monthly','quarterly','yearly','lifetime','custom') NOT NULL DEFAULT 'monthly'"
        );
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        // Normalize non-legacy values before shrinking enum again.
        DB::statement(
            "UPDATE plans SET billing_cycle = 'monthly' WHERE billing_cycle IN ('weekly','quarterly','custom')"
        );

        DB::statement(
            "ALTER TABLE plans MODIFY billing_cycle ENUM('monthly','yearly','lifetime') NOT NULL DEFAULT 'monthly'"
        );
    }
};
