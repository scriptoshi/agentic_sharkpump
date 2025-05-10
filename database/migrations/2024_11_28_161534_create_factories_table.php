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
        Schema::create('factories', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('version')->default('1');
            $table->string('chainId')->nullable();
            $table->string('foundry')->nullable();
            $table->string('contract')->nullable();
            $table->string('lock')->nullable();
            $table->json('lock_abi')->nullable();
            $table->json('factory_abi')->nullable();
            $table->json('abi')->nullable();
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
        Schema::drop('factories');
    }
};
