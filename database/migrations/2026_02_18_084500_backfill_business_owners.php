<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Backfill existing businesses with their owners in business_users table
     */
    public function up(): void
    {
        // Get all businesses that don't have an owner in business_users
        $businesses = DB::table('businesses')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('business_users')
                    ->whereColumn('business_users.business_id', 'businesses.id')
                    ->where('business_users.role', 'owner');
            })
            ->get();

        foreach ($businesses as $business) {
            // Check if record already exists (avoid duplicate)
            $exists = DB::table('business_users')
                ->where('business_id', $business->id)
                ->where('user_id', $business->user_id)
                ->exists();

            if (!$exists) {
                DB::table('business_users')->insert([
                    'business_id' => $business->id,
                    'user_id' => $business->user_id,
                    'role' => 'owner',
                    'branch_id' => null,
                    'permissions' => json_encode([]),
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Don't remove owners on rollback as it could be destructive
    }
};
