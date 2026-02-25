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
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'preferred_locale')) {
                $table->string('preferred_locale', 5)->default('en');
                $table->index('preferred_locale');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'preferred_locale')) {
                $table->dropIndex(['preferred_locale']);
                $table->dropColumn('preferred_locale');
            }
        });
    }
};

