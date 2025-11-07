<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWebSocketDialogMsgExtrasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('web_socket_dialog_msg_extras', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('msg_id')->nullable()->default(0)->comment('消息ID');
            $table->longText('data')->nullable()->comment('额外数据');
            $table->timestamps();

            $table->foreign('msg_id')->references('id')->on('web_socket_dialog_msgs')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('web_socket_dialog_msg_extras');
    }
}
