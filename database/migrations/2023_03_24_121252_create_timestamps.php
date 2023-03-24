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
        Schema::table('kafka_log', function (Blueprint $table) {
            $table->timestamps(6);
        });

        \Illuminate\Support\Facades\DB::statement('UPDATE kafka_log SET created_at = CURRENT_TIMESTAMP(6)');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
    }
};
