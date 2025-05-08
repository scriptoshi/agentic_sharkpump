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
        // Bot Commands table
        Schema::create('commands', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid');
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bot_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vc_id')->nullable()->constrained()->nullOnDelete();
            $table->string('command');  // e.g., /weather, /help
            $table->string('name')->nullable();  // e.g., /weather, /help
            $table->string('description');
            $table->text('system_prompt_override')->nullable();
            $table->boolean('is_active')->default(true);
            $table->decimal('credits_per_message', 10, 2)->nullable();
            $table->integer('priority')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('commands');
    }
};
