<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddWebSocketDialogMsgReadsAddSilence extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('web_socket_dialog_msg_reads', function (Blueprint $table) {
            if (!Schema::hasColumn('web_socket_dialog_msg_reads', 'silence')) {
                $table->boolean('silence')->default(0)->nullable()->after('mention')->comment('是否免打扰：0否，1是');
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
            $table->dropColumn("silence");
        });
    }
}
