<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWebSocketDialogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('web_socket_dialogs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('type', 50)->nullable()->default('')->comment('对话类型');
            $table->string('group_type', 50)->nullable()->default('')->comment('聊天室类型');
            $table->string('name', 50)->nullable()->default('')->comment('对话名称');
            $table->timestamp('last_at')->nullable()->comment('最后消息时间');
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
        Schema::dropIfExists('web_socket_dialogs');
    }
}
