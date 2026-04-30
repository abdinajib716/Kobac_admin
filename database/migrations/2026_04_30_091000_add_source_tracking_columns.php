<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('income_transactions', function (Blueprint $table) {
            $table->string('source_type', 50)->nullable()->after('reference');
            $table->unsignedBigInteger('source_id')->nullable()->after('source_type');
            $table->json('meta')->nullable()->after('created_by');
            $table->index(['source_type', 'source_id']);
        });

        Schema::table('customer_transactions', function (Blueprint $table) {
            $table->string('reference', 100)->nullable()->after('description');
            $table->string('source_type', 50)->nullable()->after('reference');
            $table->unsignedBigInteger('source_id')->nullable()->after('source_type');
            $table->json('meta')->nullable()->after('created_by');
            $table->index(['source_type', 'source_id']);
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->string('source_type', 50)->nullable()->after('reference');
            $table->unsignedBigInteger('source_id')->nullable()->after('source_type');
            $table->json('meta')->nullable()->after('created_by');
            $table->index(['source_type', 'source_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropIndex(['source_type', 'source_id']);
            $table->dropColumn(['source_type', 'source_id', 'meta']);
        });

        Schema::table('customer_transactions', function (Blueprint $table) {
            $table->dropIndex(['source_type', 'source_id']);
            $table->dropColumn(['reference', 'source_type', 'source_id', 'meta']);
        });

        Schema::table('income_transactions', function (Blueprint $table) {
            $table->dropIndex(['source_type', 'source_id']);
            $table->dropColumn(['source_type', 'source_id', 'meta']);
        });
    }
};
