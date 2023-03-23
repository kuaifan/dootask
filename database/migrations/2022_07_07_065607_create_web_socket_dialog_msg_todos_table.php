<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWebSocketDialogMsgTodosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('web_socket_dialog_msg_todos', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('dialog_id')->nullable()->default(0)->comment('对话ID');
            $table->bigInteger('msg_id')->nullable()->default(0)->comment('消息ID');
            $table->bigInteger('userid')->nullable()->default(0)->comment('接收会员ID');
            $table->timestamp('done_at')->nullable()->comment('完成时间');
            $table->index(['dialog_id', 'userid', 'done_at']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('web_socket_dialog_msg_todos');
    }
}
