<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddWebSocketDialogMsgReadsDot extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('web_socket_dialog_msg_reads', function (Blueprint $table) {
            if (!Schema::hasColumn('web_socket_dialog_msg_reads', 'dot')) {
                $table->integer('dot')->nullable()->default(0)->after('after')->comment('红点标记');
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
        Schema::table('web_socket_dialog_msg_reads', function (Blueprint $table) {
            $table->dropColumn("dot");
        });
    }
}
