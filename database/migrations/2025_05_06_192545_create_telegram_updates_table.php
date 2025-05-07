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

        Schema::create('telegram_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bot_id')->constrained('bots')->onDelete('cascade');
            $table->foreignId('message_id')->constrained()->cascadeOnDelete();
            $table->string('method');
            $table->string('tool_id')->nullable(); // openai tool id
            $table->string('tool_call_id')->nullable();
            $table->json('parameters')->nullable();
            $table->timestamp('triggered_at');
            $table->json('response')->nullable();
            $table->float('execution_time')->nullable();
            $table->boolean('success');
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {

        Schema::dropIfExists('telegram_logs');
    }
};
