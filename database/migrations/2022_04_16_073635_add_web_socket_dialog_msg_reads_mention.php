<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddWebSocketDialogMsgReadsMention extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('web_socket_dialog_msg_reads', function (Blueprint $table) {
            if (!Schema::hasColumn('web_socket_dialog_msg_reads', 'mention')) {
                $table->boolean('mention')->default(0)->after('userid')->nullable()->comment('是否提及（被@）');
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
            $table->dropColumn("mention");
        });
    }
}
