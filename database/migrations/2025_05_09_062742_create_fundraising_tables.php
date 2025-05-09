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
        Schema::create('fundraisers', function (Blueprint $table) {
            $table->id();
            $table->string('uuid');
            $table->string('name');
            $table->text('description');
            $table->integer('max')->default(10);
            $table->decimal('amount', 10, 2);
            $table->string('access')->nullable();
            $table->string('currency')->default('USD');
            $table->string('image')->nullable();
            $table->timestamps();
        });

        Schema::create('nowpays', function (Blueprint $table) {
            $table->id();
            $table->string('uuid');
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('payment_id');
            $table->string('payment_status')->default('waiting');
            $table->string('pay_address');
            $table->decimal('pay_amount', 18, 8);
            $table->string('pay_currency');
            $table->decimal('price_amount', 10, 2);
            $table->string('price_currency');
            $table->string('ipn_callback_url')->nullable();
            $table->string('order_id')->nullable();
            $table->text('order_description')->nullable();
            $table->string('purchase_id')->nullable();
            $table->nullableMorphs('payable');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fundraisers');
        Schema::dropIfExists('nowpay');
    }
};
