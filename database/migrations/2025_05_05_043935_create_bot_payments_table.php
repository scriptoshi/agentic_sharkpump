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
        // Table to track user credit balances
        Schema::create('balances', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bot_id')->constrained()->cascadeOnDelete();
            $table->decimal('balance', 10, 2)->default(0);
            $table->timestamps();
            $table->unique(['user_id', 'bot_id']);
        });

        // Table to track user credit balances
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('balance_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bot_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 10, 2)->default(0);
            $table->string('details')->nullable();
            $table->morphs('transactable');
            $table->string('type')->default('credit');
            $table->timestamps();
        });

        // Table to track star payments 
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->uuid('payload')->unique(); // Added unique constraint for UUID
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // Bot owner
            $table->foreignId('bot_id')->constrained()->cascadeOnDelete();
            $table->foreignId('balance_id')->constrained()->cascadeOnDelete();
            $table->string('telegram_payment_charge_id')->nullable()->unique(); // Unique Telegram payment identifier
            $table->string('currency')->default('XTR'); // e.g., 'XTR' for Telegram Star
            $table->integer('amount'); // Amount in XTR
            $table->decimal('credits_earned', 10, 2); // Credits earned from this payment
            $table->json('payment_data')->nullable(); //  Any Extra payments data can be save here.
            $table->timestamp('paid_at')->nullable(); // Use nullable timestamp as per Laravel convention, set on successful payment
            $table->timestamp('cancelled_at')->nullable(); // Use nullable timestamp as per Laravel convention, set on successful refund
            $table->timestamps(); // created_at and updated_at
        });

        // Table to track star payment refunds
        Schema::create('refunds', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique(); // Unique identifier for the refund record
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // Link to the original payment
            $table->foreignId('payment_id')->constrained()->cascadeOnDelete(); // Link to the original payment
            $table->foreignId('bot_id')->constrained()->cascadeOnDelete();
            $table->foreignId('balance_id')->constrained()->cascadeOnDelete();
            $table->string('telegram_payment_charge_id')->unique(); // Telegram's identifier for the refund transaction
            $table->bigInteger('refunded_amount'); // Amount refunded in the smallest units of the currency
            $table->string('currency')->default('XTR'); // Currency of the refund
            $table->text('reason')->nullable(); // Optional reason for the refund
            $table->timestamp('refunded_at')->nullable();
            $table->timestamps(); // created_at and updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('refunds'); // Drop the new refunds table first
        Schema::dropIfExists('payments'); // Drop the renamed payments table
        Schema::dropIfExists('balances');
    }
};
