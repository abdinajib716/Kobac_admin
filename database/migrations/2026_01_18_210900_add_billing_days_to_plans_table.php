<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Add dynamic billing days field to plans table for custom billing cycles.
     */
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            // Add billing_days for custom billing cycle (e.g., 7 days, 30 days, 90 days, 365 days)
            $table->integer('billing_days')->nullable()->after('billing_cycle');
        });

        // Modify billing_cycle enum to include 'custom' option
        // Note: We'll handle the custom billing cycle in the model
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn('billing_days');
        });
    }
};
