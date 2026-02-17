<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Add fields for mobile app users (Individual & Business)
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('user_type', ['client', 'individual', 'business'])->default('client')->after('id');
            $table->string('phone', 20)->nullable()->after('email');
            $table->boolean('is_active')->default(true)->after('remember_token');
            $table->timestamp('deactivated_at')->nullable()->after('is_active');
            $table->foreignId('deactivated_by')->nullable()->after('deactivated_at')->constrained('users')->nullOnDelete();
            
            $table->index('user_type');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['deactivated_by']);
            $table->dropIndex(['user_type']);
            $table->dropIndex(['is_active']);
            $table->dropColumn(['user_type', 'phone', 'is_active', 'deactivated_at', 'deactivated_by']);
        });
    }
};
