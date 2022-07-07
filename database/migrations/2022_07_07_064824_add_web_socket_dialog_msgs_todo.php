<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddWebSocketDialogMsgsTodo extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('web_socket_dialog_msgs', function (Blueprint $table) {
            if (!Schema::hasColumn('web_socket_dialog_msgs', 'todo')) {
                $table->bigInteger('todo')->nullable()->default(0)->after('tag')->comment('设为待办会员ID');
            }
        });
        Schema::table('web_socket_dialog_msg_reads', function (Blueprint $table) {
            if (Schema::hasColumn('web_socket_dialog_msg_reads', 'userid')) {
                $table->bigInteger('userid')->nullable()->default(0)->comment('接收会员ID')->change();
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('web_socket_dialog_msgs', function (Blueprint $table) {
            $table->dropColumn("todo");
        });
    }
}
