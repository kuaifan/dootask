<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWebSocketDialogMsgsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('web_socket_dialog_msgs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('dialog_id')->nullable()->default(0)->comment('对话ID');
            $table->bigInteger('userid')->nullable()->default(0)->comment('发送会员ID');
            $table->string('type', 50)->nullable()->default('')->comment('消息类型');
            $table->longText('msg')->nullable()->comment('详细消息');
            $table->integer('read')->nullable()->default(0)->comment('已阅数量');
            $table->integer('send')->nullable()->default(0)->comment('发送数量');
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
        Schema::dropIfExists('web_socket_dialog_msgs');
    }
}
