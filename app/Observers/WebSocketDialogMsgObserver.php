<?php

namespace App\Observers;

use App\Models\WebSocketDialogMsg;
use App\Tasks\ZincSearchSyncTask;

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
        self::taskDeliver(new ZincSearchSyncTask('sync', $webSocketDialogMsg->toArray()));
    }

    /**
     * Handle the WebSocketDialogMsg "updated" event.
     *
     * @param  \App\Models\WebSocketDialogMsg  $webSocketDialogMsg
     * @return void
     */
    public function updated(WebSocketDialogMsg $webSocketDialogMsg)
    {
        self::taskDeliver(new ZincSearchSyncTask('sync', $webSocketDialogMsg->toArray()));
    }

    /**
     * Handle the WebSocketDialogMsg "deleted" event.
     *
     * @param  \App\Models\WebSocketDialogMsg  $webSocketDialogMsg
     * @return void
     */
    public function deleted(WebSocketDialogMsg $webSocketDialogMsg)
    {
        self::taskDeliver(new ZincSearchSyncTask('delete', $webSocketDialogMsg->toArray()));
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
        //
    }
}
