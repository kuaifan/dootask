<?php

namespace App\Observers;

use App\Models\WebSocketDialogMsg;
use App\Module\ZincSearch\ZincSearchDialogUserMsg;

class WebSocketDialogMsgObserver
{
    /**
     * Handle the WebSocketDialogMsg "created" event.
     *
     * @param  \App\Models\WebSocketDialogMsg  $webSocketDialogMsg
     * @return void
     */
    public function created(WebSocketDialogMsg $webSocketDialogMsg)
    {
        ZincSearchDialogUserMsg::syncMsg($webSocketDialogMsg);
    }

    /**
     * Handle the WebSocketDialogMsg "updated" event.
     *
     * @param  \App\Models\WebSocketDialogMsg  $webSocketDialogMsg
     * @return void
     */
    public function updated(WebSocketDialogMsg $webSocketDialogMsg)
    {
        ZincSearchDialogUserMsg::syncMsg($webSocketDialogMsg);
    }

    /**
     * Handle the WebSocketDialogMsg "deleted" event.
     *
     * @param  \App\Models\WebSocketDialogMsg  $webSocketDialogMsg
     * @return void
     */
    public function deleted(WebSocketDialogMsg $webSocketDialogMsg)
    {
        ZincSearchDialogUserMsg::deleteMsg($webSocketDialogMsg);
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
