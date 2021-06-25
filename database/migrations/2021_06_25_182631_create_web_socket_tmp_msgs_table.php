<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWebSocketTmpMsgsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('web_socket_tmp_msgs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('md5', 50)->nullable()->default('')->unique('pre_ws_tmp_msgs_md5_unique')->comment('MD5(会员ID-消息)');
            $table->longText('msg')->nullable()->comment('详细消息');
            $table->tinyInteger('send')->nullable()->default(0)->index('pre_ws_tmp_msgs_send_index')->comment('是否已发送');
            $table->bigInteger('create_id')->nullable()->default(0)->index('pre_ws_tmp_msgs_create_id_index')->comment('所属会员ID');
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
        Schema::dropIfExists('web_socket_tmp_msgs');
    }
}
