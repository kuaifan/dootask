<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddWebSocketDialogMsgsReplyNum extends Migration
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
            if (!Schema::hasColumn('web_socket_dialog_msgs', 'reply_num')) {
                $isAdd = true;
                $table->bigInteger('reply_num')->nullable()->default(0)->after('send')->comment('有多少条回复');
            }
        });
        if ($isAdd) {
            \App\Models\WebSocketDialogMsg::select(['reply_id'])
                ->distinct()
                ->where('reply_id', '>', 0)
                ->chunk(100, function ($lists) {
                    /** @var \App\Models\WebSocketDialogMsg $item */
                    foreach ($lists as $item) {
                        \App\Models\WebSocketDialogMsg::whereId($item->reply_id)->update([
                            'reply_num' => \App\Models\WebSocketDialogMsg::whereReplyId($item->reply_id)->count()
                        ]);
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
            $table->dropColumn("reply_num");
        });
    }
}
