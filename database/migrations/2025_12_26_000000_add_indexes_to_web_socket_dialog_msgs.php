<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIndexesToWebSocketDialogMsgs extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('web_socket_dialog_msgs', function (Blueprint $table) {
            $table->index(['dialog_id', 'deleted_at', 'id']);
            $table->index(['reply_id', 'deleted_at']);
        });

        Schema::table('web_socket_dialog_msg_reads', function (Blueprint $table) {
            $table->index(['userid', 'silence', 'read_at'], 'idx_ws_msg_reads_userid_silence_read_at');
            $table->index(['dialog_id', 'userid', 'mention', 'read_at'], 'idx_ws_msg_reads_dialog_user_mention_read_at');
        });

        Schema::table('user_checkin_records', function (Blueprint $table) {
            $table->index(['userid', 'mac', 'date'], 'idx_user_checkin_records_userid_mac_date');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // No-op: do not drop indexes automatically.
    }
}
