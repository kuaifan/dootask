<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserBotsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('user_bots'))
            return;

        Schema::create('user_bots', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('userid')->nullable()->default(0)->comment('所属人ID');
            $table->bigInteger('bot_id')->nullable()->default(0)->comment('机器人ID');
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
        Schema::dropIfExists('user_bots');
    }
}
