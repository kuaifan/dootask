<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBotToWebSocketDialogMsgs extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('web_socket_dialog_msgs', function (Blueprint $table) {
            if (!Schema::hasColumn('web_socket_dialog_msgs', 'bot')) {
                $table->tinyInteger('bot')->nullable()->default(0)->after('modify')->comment('是否机器人的消息');
                $table->index('bot');
            }
        });

        // 获取表前缀
        $prefix = DB::getTablePrefix();

        // 使用原生SQL更新数据
        /** @noinspection SqlNoDataSourceInspection */
        DB::statement("
            UPDATE {$prefix}web_socket_dialog_msgs m
            INNER JOIN {$prefix}users u ON u.userid = m.userid
            SET m.bot = 1
            WHERE u.bot = 1
        ");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('web_socket_dialog_msgs', function (Blueprint $table) {
            if (Schema::hasColumn('web_socket_dialog_msgs', 'bot')) {
                $table->dropIndex('bot');
                $table->dropColumn('bot');
            }
        });
    }
}
