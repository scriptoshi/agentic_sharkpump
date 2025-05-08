<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\ApiType;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('apis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('url', 2048);
            $table->string('website', 2048)->nullable();
            $table->string('content_type', 100)->default('application/json');
            $table->enum('auth_type', ['none', 'basic', 'bearer', 'api_key', 'query_param'])->default('none');
            $table->string('auth_username')->nullable();
            $table->string('auth_password')->nullable();
            $table->string('auth_token', 1024)->nullable();
            $table->string('auth_query_key', 1024)->nullable();
            $table->string('auth_query_value', 1024)->nullable();
            $table->boolean('active')->default(true);
            $table->boolean('is_public')->default(false);
            $table->text('description')->nullable();
            $table->string('type')->default(ApiType::USER->value);
            $table->timestamps();
            $table->index('user_id');
            $table->index('active');
        });

        Schema::create('api_headers', function (Blueprint $table) {
            $table->id();
            $table->morphs('headerable');
            $table->string('header_name');
            $table->text('header_value');
            $table->timestamps();
        });


        Schema::create('api_tools', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('api_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description');
            $table->boolean('shouldQueue')->default(false);
            $table->string('version')->default('1.0.0');
            $table->string('add_user_to_request')->nullable();
            $table->enum('method', ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'])->default('POST');
            $table->string('path', 2048)->nullable();
            $table->string('query_params', 2048)->nullable();
            $table->json('tool_config')->nullable();
            $table->boolean('strict')->default(true);
            $table->timestamps();
            $table->index('api_id', 2048)->nullable();
        });


        Schema::create('api_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('api_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('api_tool_id')->constrained()->onDelete('cascade');
            $table->timestamp('triggered_at')->useCurrent();
            $table->unsignedInteger('response_code')->nullable();
            $table->text('response_body')->nullable();
            $table->float('execution_time')->nullable()->comment('Time in seconds');
            $table->boolean('success')->nullable();
            $table->text('error_message')->nullable();
            $table->index('triggered_at');
        });

        Schema::create('api_auth', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('api_id')->constrained()->onDelete('cascade');
            $table->string('auth_username')->nullable();
            $table->string('auth_password')->nullable();
            $table->string('auth_token', 1024)->nullable();
            $table->string('auth_query_value', 1024)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('api_logs');
        Schema::dropIfExists('api_headers');
        Schema::dropIfExists('api_tools');
        Schema::dropIfExists('apis');
    }
};
