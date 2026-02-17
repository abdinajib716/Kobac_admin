<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Create countries, regions, and districts tables for location-based registration.
     */
    public function up(): void
    {
        // Countries table
        Schema::create('countries', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 3)->unique(); // ISO 3166-1 alpha-3 code
            $table->string('code_alpha2', 2)->nullable(); // ISO 3166-1 alpha-2 code
            $table->string('phone_code', 10)->nullable();
            $table->string('currency', 3)->nullable();
            $table->string('flag')->nullable(); // emoji or image path
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            
            $table->index('is_active');
        });

        // Regions table (states/provinces)
        Schema::create('regions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('country_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('code', 10)->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            
            $table->index(['country_id', 'is_active']);
        });

        // Districts table
        Schema::create('districts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('region_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('code', 10)->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            
            $table->index(['region_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('districts');
        Schema::dropIfExists('regions');
        Schema::dropIfExists('countries');
    }
};
