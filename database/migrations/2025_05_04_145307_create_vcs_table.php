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
        /**
         * open ai vector store
         */
        Schema::create('vcs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bot_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('vector_id')->unique();
            $table->string('vector_name');
            $table->integer('max_num_results')->default(5);
            $table->enum('status', ['expired', 'in_progress', 'completed'])->default('in_progress');
            $table->timestamp('last_active_at')->nullable();
            $table->integer('expires_in_days')->default(30);
            $table->timestamps();
        });

        /**
         * open ai vector files
         */
        Schema::create('files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bot_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('file_id')->unique();
            $table->string('file_name');
            $table->string('bytes');
            $table->timestamps();
        });


        /**
         * Many to Many
         */
        Schema::create('file_vc', function (Blueprint $table) {
            $table->foreignId('vc_id')->constrained()->cascadeOnDelete();
            $table->foreignId('file_id')->constrained()->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('file_vc');
        Schema::dropIfExists('files');
        Schema::dropIfExists('vcs');
    }
};
