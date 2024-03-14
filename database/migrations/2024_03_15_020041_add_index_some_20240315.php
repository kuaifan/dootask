<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIndexSome20240315 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('web_socket_dialog_msg_reads', function (Blueprint $table) {
            $table->index(['dialog_id', 'userid', 'read_at', 'msg_id'], 'IDEX_dialog_id_userid_read_at_msg_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return voidw
     */
    public function down()
    {
        Schema::table('web_socket_dialog_msg_reads', function (Blueprint $table) {
            $table->dropIndex('IDEX_dialog_id_userid_read_at_msg_id');
        });
    }
}
