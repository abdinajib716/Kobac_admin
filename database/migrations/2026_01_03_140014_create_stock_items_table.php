<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Stock Items - Business Only
     */
    public function up(): void
    {
        Schema::create('stock_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->onDelete('cascade');
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('sku', 50)->nullable();
            $table->text('description')->nullable();
            $table->decimal('quantity', 15, 2)->default(0);
            $table->string('unit', 20)->default('pcs');
            $table->decimal('cost_price', 15, 2)->nullable();
            $table->decimal('selling_price', 15, 2)->nullable();
            $table->string('image')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index(['business_id', 'is_active']);
            $table->index(['business_id', 'branch_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_items');
    }
};
