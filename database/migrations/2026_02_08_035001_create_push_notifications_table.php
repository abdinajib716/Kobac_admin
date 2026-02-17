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
        Schema::create('push_notifications', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('body');
            $table->json('data')->nullable();
            $table->string('image_url')->nullable();
            $table->enum('audience', ['all', 'individual', 'business', 'specific'])->default('all');
            $table->foreignId('target_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('topic')->nullable();
            $table->integer('total_recipients')->default(0);
            $table->integer('success_count')->default(0);
            $table->integer('failure_count')->default(0);
            $table->enum('status', ['pending', 'sending', 'sent', 'partial', 'failed'])->default('pending');
            $table->text('error_message')->nullable();
            $table->json('failed_tokens')->nullable();
            $table->foreignId('sent_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('audience');
            $table->index('sent_by');
            $table->index('sent_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('push_notifications');
    }
};
