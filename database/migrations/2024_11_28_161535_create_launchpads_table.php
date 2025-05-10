<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('launchpads', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('user_id')->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('factory_id')->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->string('contract', 44)->nullable();
            $table->string('token', 44)->nullable();
            $table->string('pool', 44)->nullable();
            $table->string('graph', 600)->nullable();
            $table->string('name', 32);
            $table->string('symbol', 10);
            $table->string('description', 3000);
            $table->string('chainId');
            $table->string('twitter')->nullable();
            $table->string('discord')->nullable();
            $table->string('telegram')->nullable();
            $table->string('website')->nullable();
            $table->string('status')->default('pending');
            $table->string('logo')->nullable();
            $table->boolean('featured')->default(false);
            $table->boolean('kingofthehill')->default(false);
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('launchpads');
    }
};
