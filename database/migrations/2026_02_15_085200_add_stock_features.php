<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adds:
     * - quantity_before to stock_movements for tracking
     * - alert_threshold to stock_items for low stock alerts
     */
    public function up(): void
    {
        // Add quantity_before to stock_movements
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->decimal('quantity_before', 15, 2)->after('quantity')->default(0);
        });

        // Add alert_threshold to stock_items for low stock alerts
        Schema::table('stock_items', function (Blueprint $table) {
            $table->decimal('alert_threshold', 15, 2)->nullable()->after('quantity');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropColumn('quantity_before');
        });

        Schema::table('stock_items', function (Blueprint $table) {
            $table->dropColumn('alert_threshold');
        });
    }
};
