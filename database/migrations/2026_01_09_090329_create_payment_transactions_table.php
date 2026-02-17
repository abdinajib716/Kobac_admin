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
        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            
            $table->string('reference_id')->unique()->comment('Unique internal reference');
            $table->string('invoice_id')->nullable()->comment('Invoice/Order ID');
            $table->string('waafi_transaction_id')->nullable()->comment('WaafiPay transaction ID');
            
            $table->string('payment_method')->default('WALLET_ACCOUNT');
            $table->string('wallet_type')->nullable()->comment('EVC Plus, Zaad, Jeeb, Sahal');
            $table->string('phone_number', 20)->comment('Customer phone (252XXXXXXXXX)');
            $table->string('customer_name')->nullable();
            
            $table->decimal('amount', 10, 2)->comment('Transaction amount');
            $table->string('currency', 3)->default('USD');
            $table->text('description')->nullable();
            
            $table->enum('status', ['pending', 'processing', 'success', 'failed', 'cancelled', 'refunded'])->default('pending');
            $table->string('status_code')->nullable()->comment('WaafiPay response code');
            $table->text('status_message')->nullable()->comment('WaafiPay response message');
            
            $table->json('request_payload')->nullable();
            $table->json('response_payload')->nullable();
            $table->text('error_message')->nullable();
            
            $table->string('channel')->default('WEB')->comment('WEB, MOBILE, API');
            $table->string('environment')->default('LIVE')->comment('LIVE, SANDBOX, TEST');
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            
            $table->timestamp('initiated_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['status', 'created_at']);
            $table->index('phone_number');
            $table->index('waafi_transaction_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_transactions');
    }
};
