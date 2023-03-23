<?php

@error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddWebSocketDialogMsgsKey extends Migration
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
            if (!Schema::hasColumn('web_socket_dialog_msgs', 'key')) {
                $isAdd = true;
                $table->text('key')->after('emoji')->nullable()->default('')->comment('搜索关键词');
            }
        });
        if ($isAdd) {
            \App\Models\WebSocketDialogMsg::chunkById(100, function ($lists) {
                /** @var \App\Models\WebSocketDialogMsg $item */
                foreach ($lists as $item) {
                    $key = $item->generateMsgKey();
                    if ($key) {
                        $item->key = $key;
                        $item->save();
                    }
                }
            });
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
            $table->dropColumn("key");
        });
    }
}
