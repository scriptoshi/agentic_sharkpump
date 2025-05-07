<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\MessageRole;
use App\Enums\ToolcallStatus;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('chats', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bot_id')->constrained()->cascadeOnDelete();
            $table->uuid('uuid')->unique();
            $table->string('telegram_chat_id')->nullable()->index();
            $table->string('telegram_user_id')->index();
            $table->string('ai_conversation_id')->nullable()->index();
            $table->unique(['bot_id', 'telegram_user_id']);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('telegram_updates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bot_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('chat_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('command_id')->nullable()->constrained()->nullOnDelete();
            $table->bigInteger('telegram_update_id')->unique();
            $table->json('message')->nullable();
            $table->string('type');
            $table->json('edited_message')->nullable();
            $table->json('inline_query')->nullable();
            $table->json('callback_query')->nullable();
            $table->json('pre_checkout_query')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('bot_id')->constrained()->cascadeOnDelete();
            $table->foreignId('chat_id')->constrained()->cascadeOnDelete();
            $table->foreignId('telegram_update_id')->constrained('telegram_updates')->cascadeOnDelete();
            $table->string('ai_message_id')->nullable()->index(); // Openai / Anthropic's message ID
            $table->string('role')->nullable()->default(MessageRole::USER->value);
            $table->json('content')->nullable(); // Store as JSON to handle multiple content blocks
            $table->string('stop_reason')->nullable();
            $table->integer('input_tokens')->nullable();
            $table->integer('output_tokens')->nullable();
            $table->json('metadata')->nullable(); // For any additional data we might want to store
            $table->timestamps();
            // Order matters for rebuilding context
            $table->index(['chat_id', 'created_at']);
        });

        /**
         * Responses from the ai
         */
        Schema::create('tool_calls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bot_id')->constrained()->cascadeOnDelete();
            $table->foreignId('chat_id')->constrained()->cascadeOnDelete();
            $table->foreignId('api_tool_id')->constrained('api_tools')->cascadeOnDelete();
            $table->foreignId('message_id')->constrained()->cascadeOnDelete();
            $table->string('tool_id')->nullable()->index(); // openAi tool id
            $table->string('tool_call_id')->index(); // Anthropic's tool call ID
            $table->string('name'); // Tool name
            $table->json('input'); // Tool input parameters
            $table->json('output')->nullable(); // Tool output/response
            $table->string('status')->default(ToolcallStatus::PENDING->value);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tool_calls');
        Schema::dropIfExists('messages');
        Schema::dropIfExists('chats');
        Schema::dropIfExists('telegram_updates');
    }
};
