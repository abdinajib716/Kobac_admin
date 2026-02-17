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
        Schema::create('expense_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('business_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('account_id')->constrained()->onDelete('cascade');
            $table->decimal('amount', 15, 2);
            $table->string('description', 500)->nullable();
            $table->string('category', 100)->nullable();
            $table->string('reference', 100)->nullable();
            $table->date('transaction_date');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
            
            $table->index(['user_id', 'transaction_date'], 'exp_trans_user_date_idx');
            $table->index(['business_id', 'branch_id', 'transaction_date'], 'exp_trans_biz_branch_date_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expense_transactions');
    }
};
