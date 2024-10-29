<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWebSocketDialogMsgTranslatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('web_socket_dialog_msg_translates'))
            return;

        Schema::create('web_socket_dialog_msg_translates', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('dialog_id')->nullable()->default(0)->comment('对话ID');
            $table->bigInteger('msg_id')->nullable()->default(0)->comment('消息ID');
            $table->string('language', 50)->nullable()->default('')->comment('语言');
            $table->longText('content')->nullable()->comment('翻译内容');
            $table->index(['msg_id', 'language']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('web_socket_dialog_msg_translates');
    }
}
