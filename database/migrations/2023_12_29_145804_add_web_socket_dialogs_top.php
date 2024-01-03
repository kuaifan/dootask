<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddWebSocketDialogsTop extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('web_socket_dialog_msgs', function (Blueprint $table) {
            if (Schema::hasColumn('web_socket_dialog_msgs', 'top')) {
                $table->dropColumn("top");
                $table->dropColumn("top_at");
            }
        });
        //
        Schema::table('web_socket_dialogs', function (Blueprint $table) {
            $table->bigInteger('top_userid')->nullable()->default(0)->after('link_id')->comment('置顶的用户ID');
            $table->bigInteger('top_msg_id')->nullable()->default(0)->after('top_userid')->comment('置顶的消息ID');
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
            if (!Schema::hasColumn('web_socket_dialog_msgs', 'top')) {
                $table->bigInteger('top')->nullable()->default(0)->after('send')->comment('置顶的会员ID');
                $table->timestamp('top_at')->nullable()->after('top')->comment('置顶时间');
            }
        });
        //
        Schema::table('web_socket_dialogs', function (Blueprint $table) {
            if (Schema::hasColumn('web_socket_dialogs', 'top_msg_id')) {
                $table->dropColumn("top_msg_id");
            }
            if (Schema::hasColumn('web_socket_dialogs', 'top_userid')) {
                $table->dropColumn("top_userid");
            }
        });
    }
}
