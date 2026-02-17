<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Add location fields (country, region, district) to users table.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('country_id')->nullable()->after('phone')->constrained()->nullOnDelete();
            $table->foreignId('region_id')->nullable()->after('country_id')->constrained()->nullOnDelete();
            $table->foreignId('district_id')->nullable()->after('region_id')->constrained()->nullOnDelete();
            $table->string('address')->nullable()->after('district_id');
            
            $table->index(['country_id', 'region_id', 'district_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['country_id']);
            $table->dropForeign(['region_id']);
            $table->dropForeign(['district_id']);
            $table->dropIndex(['country_id', 'region_id', 'district_id']);
            $table->dropColumn(['country_id', 'region_id', 'district_id', 'address']);
        });
    }
};
