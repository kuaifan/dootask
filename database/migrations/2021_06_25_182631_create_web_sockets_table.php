<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWebSocketsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('web_sockets', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('key', 50)->default('')->unique('pre_ws_key_unique');
            $table->string('fd', 50)->nullable()->default('');
            $table->string('path', 255)->nullable()->default('');
            $table->bigInteger('userid')->nullable()->default(0)->index('pre_ws_userid_index');
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
        Schema::dropIfExists('web_sockets');
    }
}
