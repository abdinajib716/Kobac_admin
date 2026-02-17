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
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_item_id')->constrained()->onDelete('cascade');
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('type', ['increase', 'decrease']);
            $table->decimal('quantity', 15, 2);
            $table->decimal('quantity_after', 15, 2);
            $table->string('reason')->nullable();
            $table->string('reference', 100)->nullable();
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
            
            $table->index(['stock_item_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
