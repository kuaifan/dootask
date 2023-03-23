<?php

namespace App\Tasks;

@error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

use App\Models\UserBot;
use App\Models\WebSocketDialogMsg;
use Carbon\Carbon;

/**
 * 删除机器人消息
 */
class DeleteBotMsgTask extends AbstractTask
{

    public function __construct()
    {
        parent::__construct();
    }

    public function start()
    {
        $bots = UserBot::where('clear_at', '<=', Carbon::now())->take(100)->get();
        foreach ($bots as $bot) {
            WebSocketDialogMsg::whereUserid($bot->bot_id)
                ->where('created_at', '<=', Carbon::now()->subDays($bot->clear_day))
                ->orderBy('id')
                ->chunk(1000, function ($msgs) {
                    $ids = $msgs->pluck('id')->toArray();
                    if ($ids) {
                        WebSocketDialogMsg::deleteMsgs($ids);
                    }
                });
            $bot->clear_at = Carbon::now()->addDays($bot->clear_day);
            $bot->save();
        }
    }

    public function end()
    {

    }
}
