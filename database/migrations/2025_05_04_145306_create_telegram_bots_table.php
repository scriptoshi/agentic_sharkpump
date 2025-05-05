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
            $table->uuid('uuid');
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('username')->unique();
            $table->string('bot_token')->unique();
            $table->string('bot_provider')->default(BotProvider::ANTHROPIC->value);
            $table->text('api_key')->nullable();
            $table->text('system_prompt')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_cloneable')->default(false);
            $table->json('settings')->nullable();
            $table->integer('credits_per_star')->default(0);
            $table->decimal('credits_per_message', 10, 2)->default(0);
            $table->timestamp('last_active_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // Bot Commands table
        Schema::create('commands', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid');
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bot_id')->constrained()->cascadeOnDelete();
            $table->string('command');  // e.g., /weather, /help
            $table->string('name')->nullable();  // e.g., /weather, /help
            $table->string('description');
            $table->text('system_prompt_override')->nullable();
            $table->boolean('is_active')->default(true);
            $table->decimal('credits_per_message', 10, 2)->nullable();
            $table->integer('priority')->default(0);
            $table->timestamps();
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
        Schema::dropIfExists('commands');
        Schema::dropIfExists('bots');
    }
};
