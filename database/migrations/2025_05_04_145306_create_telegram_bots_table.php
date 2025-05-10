<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\BotProvider;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {

        // Telegram Bots table
        Schema::create('bots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('launchpad_id')->constrained()->cascadeOnDelete();
            $table->uuid('uuid');
            $table->ulid('webhook_secret')->unique();
            $table->string('name');
            $table->string('description')->nullable();
            $table->string('logo')->nullable();
            $table->string('username')->unique();
            $table->string('bot_token')->unique();
            $table->string('bot_provider')->default(BotProvider::ANTHROPIC->value);
            $table->string('ai_model')->nullable();
            $table->text('api_key')->nullable();
            $table->text('system_prompt')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_cloneable')->default(false);
            $table->json('settings')->nullable();
            $table->integer('credits_per_star')->default(1);
            $table->decimal('credits_per_message', 10, 2)->default(1);
            $table->timestamp('last_active_at')->nullable();
            $table->decimal('ai_temperature', 10, 2)->default(1);
            $table->integer('ai_max_tokens')->default(2048);
            $table->boolean('ai_store')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });
        // link bots / commands to tools polymorphic table
        Schema::create('toolables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('api_tool_id')->constrained('api_tools')->cascadeOnDelete();
            $table->morphs('toolable');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('toolables');
        Schema::dropIfExists('bots');
    }
};
