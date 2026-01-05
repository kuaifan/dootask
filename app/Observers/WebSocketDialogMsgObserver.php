<?php

namespace App\Observers;

use App\Models\WebSocketDialogMsg;
use App\Module\Apps;
use App\Module\Manticore\ManticoreMsg;
use App\Tasks\ManticoreSyncTask;

class WebSocketDialogMsgObserver extends AbstractObserver
{
    /**
     * Handle the WebSocketDialogMsg "created" event.
     *
     * @param  \App\Models\WebSocketDialogMsg  $webSocketDialogMsg
     * @return void
     */
    public function created(WebSocketDialogMsg $webSocketDialogMsg)
    {
        // Manticore 同步（仅在安装 Manticore 且符合索引条件时）
        if (Apps::isInstalled('search') && ManticoreMsg::shouldIndex($webSocketDialogMsg)) {
            self::taskDeliver(new ManticoreSyncTask('msg_sync', ['msg_id' => $webSocketDialogMsg->id]));
        }
    }

    /**
     * Handle the WebSocketDialogMsg "updated" event.
     *
     * @param  \App\Models\WebSocketDialogMsg  $webSocketDialogMsg
     * @return void
     */
    public function updated(WebSocketDialogMsg $webSocketDialogMsg)
    {
        // Manticore 同步（更新可能使消息符合或不再符合索引条件，由 sync 方法处理）
        if (Apps::isInstalled('search')) {
            self::taskDeliver(new ManticoreSyncTask('msg_sync', ['msg_id' => $webSocketDialogMsg->id]));
        }
    }

    /**
     * Handle the WebSocketDialogMsg "deleted" event.
     *
     * @param  \App\Models\WebSocketDialogMsg  $webSocketDialogMsg
     * @return void
     */
    public function deleted(WebSocketDialogMsg $webSocketDialogMsg)
    {
        // Manticore 删除
        if (Apps::isInstalled('search')) {
            self::taskDeliver(new ManticoreSyncTask('msg_delete', ['msg_id' => $webSocketDialogMsg->id]));
        }
    }

    /**
     * Handle the WebSocketDialogMsg "restored" event.
     *
     * @param  \App\Models\WebSocketDialogMsg  $webSocketDialogMsg
     * @return void
     */
    public function restored(WebSocketDialogMsg $webSocketDialogMsg)
    {
        //
    }

    /**
     * Handle the WebSocketDialogMsg "force deleted" event.
     *
     * @param  \App\Models\WebSocketDialogMsg  $webSocketDialogMsg
     * @return void
     */
    public function forceDeleted(WebSocketDialogMsg $webSocketDialogMsg)
    {
        // Manticore 删除
        if (Apps::isInstalled('search')) {
            self::taskDeliver(new ManticoreSyncTask('msg_delete', ['msg_id' => $webSocketDialogMsg->id]));
        }
    }
}
