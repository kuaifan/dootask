<?php

use App\Models\WebSocketDialog;
use App\Models\WebSocketDialogMsg;
use App\Models\WebSocketDialogMsgRead;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RemoveWebSocketDialogMsgReadsLive extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('web_socket_dialog_msg_reads', function (Blueprint $table) use (&$isAdd) {
            if (Schema::hasColumn('web_socket_dialog_msg_reads', 'live')) {
                $table->dropIndex(['userid', 'live', 'msg_id']);
                $table->dropColumn('live');
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
        //
    }
}
