<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class HandleWebSocketDialogMsgsEmoticon extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('web_socket_dialog_msgs')->where('mtype', 'image')->where('msg', 'LIKE', '%"emoticon%')->update([
            'mtype' => 'emoticon'
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {

    }
}
