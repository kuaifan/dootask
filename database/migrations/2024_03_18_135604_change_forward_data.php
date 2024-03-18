<?php

use App\Module\Base;
use App\Models\WebSocketDialogMsg;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeForwardData extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('web_socket_dialog_msgs', function (Blueprint $table) {
            if (Schema::hasColumn('web_socket_dialog_msgs', 'forward_show')) {
                WebSocketDialogMsg::where("forward_id", ">", 0)->chunk(100, function ($items) {
                    /** @var WebSocketDialogMsg $item */
                    foreach ($items as $item) {
                        $msg = Base::json2array($item->getRawOriginal('msg'));
                        $msg['forward_data'] = [
                            'id' => $item->forward_id,
                            'userid' => 0,
                            'parent_id' => $item->forward_id,
                            'parent_userid' => 0,
                            'show' => 0,
                        ];
                        $original = WebSocketDialogMsg::select(['id', 'userid', 'forward_show'])->whereId($item->forward_id)->withTrashed()->first();
                        if ($original) {
                            $msg['forward_data']['userid'] = $original->userid;
                            $msg['forward_data']['parent_userid'] = $original->userid;
                            $msg['forward_data']['show'] = $original->forward_show;
                        }
                        $item->msg = Base::array2json($msg);
                        $item->save();
                    }
                });
                $table->dropColumn("forward_show");
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
