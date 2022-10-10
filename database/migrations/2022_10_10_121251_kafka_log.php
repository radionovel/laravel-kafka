<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('kafka_log', function (Blueprint $table) {

            $table->string('uuid')->primary();
            $table->string('topic');
            $table->string('key')->nullable();
            $table->string('event_type')->nullable();
            $table->json('payload');
            $table->enum('status', ['pending', 'completed', 'error'])->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('kafka_log');
    }
};
