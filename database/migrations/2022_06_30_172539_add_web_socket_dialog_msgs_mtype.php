<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddWebSocketDialogMsgsMtype extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $isAdd = false;
        Schema::table('web_socket_dialog_msgs', function (Blueprint $table) use (&$isAdd) {
            if (!Schema::hasColumn('web_socket_dialog_msgs', 'mtype')) {
                $isAdd = true;
                $table->string('mtype', 20)->nullable()->default('')->after('type')->comment('消息类型（用于搜索）');
            }
        });
        if ($isAdd) {
            DB::table('web_socket_dialog_msgs')->update([
                'mtype' => DB::raw('type')
            ]);
            DB::table('web_socket_dialog_msgs')->where('type', 'text')->where('msg', 'LIKE', '%<img %')->update([
                'mtype' => 'image'
            ]);
            DB::table('web_socket_dialog_msgs')->where('type', 'file')->where('msg', 'LIKE', '%.jpg"%')->update([
                'mtype' => 'image'
            ]);
            DB::table('web_socket_dialog_msgs')->where('type', 'file')->where('msg', 'LIKE', '%.jpeg"%')->update([
                'mtype' => 'image'
            ]);
            DB::table('web_socket_dialog_msgs')->where('type', 'file')->where('msg', 'LIKE', '%.png"%')->update([
                'mtype' => 'image'
            ]);
            DB::table('web_socket_dialog_msgs')->where('type', 'file')->where('msg', 'LIKE', '%.gif"%')->update([
                'mtype' => 'image'
            ]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('web_socket_dialog_msgs', function (Blueprint $table) {
            $table->dropColumn("mtype");
        });
    }
}
