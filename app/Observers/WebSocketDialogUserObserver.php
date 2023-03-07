<?php

namespace App\Observers;

use App\Models\Deleted;
use App\Models\WebSocketDialogUser;

class WebSocketDialogUserObserver
{
    /**
     * Handle the WebSocketDialogUser "created" event.
     *
     * @param  \App\Models\WebSocketDialogUser  $webSocketDialogUser
     * @return void
     */
    public function created(WebSocketDialogUser $webSocketDialogUser)
    {
        Deleted::forget('dialog', $webSocketDialogUser->dialog_id, $webSocketDialogUser->userid);
    }

    /**
     * Handle the WebSocketDialogUser "updated" event.
     *
     * @param  \App\Models\WebSocketDialogUser  $webSocketDialogUser
     * @return void
     */
    public function updated(WebSocketDialogUser $webSocketDialogUser)
    {
        //
    }

    /**
     * Handle the WebSocketDialogUser "deleted" event.
     *
     * @param  \App\Models\WebSocketDialogUser  $webSocketDialogUser
     * @return void
     */
    public function deleted(WebSocketDialogUser $webSocketDialogUser)
    {
        Deleted::record('dialog', $webSocketDialogUser->dialog_id, $webSocketDialogUser->userid);
    }

    /**
     * Handle the WebSocketDialogUser "restored" event.
     *
     * @param  \App\Models\WebSocketDialogUser  $webSocketDialogUser
     * @return void
     */
    public function restored(WebSocketDialogUser $webSocketDialogUser)
    {
        //
    }

    /**
     * Handle the WebSocketDialogUser "force deleted" event.
     *
     * @param  \App\Models\WebSocketDialogUser  $webSocketDialogUser
     * @return void
     */
    public function forceDeleted(WebSocketDialogUser $webSocketDialogUser)
    {
        //
    }
}
