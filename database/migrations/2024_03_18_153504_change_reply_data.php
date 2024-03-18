<?php

use App\Module\Base;
use App\Models\WebSocketDialogMsg;
use Illuminate\Database\Migrations\Migration;

class ChangeReplyData extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        WebSocketDialogMsg::where("reply_id", ">", 0)->chunk(100, function ($items) {
            /** @var WebSocketDialogMsg $item */
            foreach ($items as $item) {
                $msg = Base::json2array($item->getRawOriginal('msg'));
                $msg['reply_data'] = [
                    'id' => $item->reply_id,
                    'userid' => 0,
                    'type' => '',
                    'msg' => [],
                ];
                $original = WebSocketDialogMsg::whereId($item->reply_id)->withTrashed()->first();
                if ($original) {
                    $replyMsg = Base::json2array($original->getRawOriginal('msg'));
                    unset($replyMsg['reply_data']);
                    $msg['reply_data']['userid'] = $original->userid;
                    $msg['reply_data']['type'] = $original->type;
                    $msg['reply_data']['msg'] = $replyMsg;
                }
                $item->msg = Base::array2json($msg);
                $item->save();
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
        // 回滚数据 - 无法回滚
    }
}
