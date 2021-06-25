<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWebSocketDialogMsgReadsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('web_socket_dialog_msg_reads', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('dialog_id')->nullable()->default(0)->comment('对话ID');
            $table->bigInteger('msg_id')->nullable()->default(0)->comment('消息ID');
            $table->bigInteger('userid')->nullable()->default(0)->comment('发送会员ID');
            $table->tinyInteger('after')->nullable()->default(0)->comment('在阅读之后才添加的记录');
            $table->timestamp('read_at')->nullable()->comment('阅读时间');
            $table->unique(['msg_id', 'userid'], 'IDEX_msg_id_userid');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('web_socket_dialog_msg_reads');
    }
}
