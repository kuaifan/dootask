<?php

namespace App\Observers;

use App\Models\Deleted;
use App\Models\WebSocketDialog;
use App\Models\WebSocketDialogMsgRead;
use App\Models\WebSocketDialogUser;

class WebSocketDialogObserver
{
    /**
     * Handle the WebSocketDialog "created" event.
     *
     * @param  \App\Models\WebSocketDialog  $webSocketDialog
     * @return void
     */
    public function created(WebSocketDialog $webSocketDialog)
    {
        //
    }

    /**
     * Handle the WebSocketDialog "updated" event.
     *
     * @param  \App\Models\WebSocketDialog  $webSocketDialog
     * @return void
     */
    public function updated(WebSocketDialog $webSocketDialog)
    {
        //
    }

    /**
     * Handle the WebSocketDialog "deleted" event.
     *
     * @param  \App\Models\WebSocketDialog  $webSocketDialog
     * @return void
     */
    public function deleted(WebSocketDialog $webSocketDialog)
    {
        Deleted::record('dialog', $webSocketDialog->id, $this->userids($webSocketDialog));
        WebSocketDialogMsgRead::whereDialogId($webSocketDialog->id)->update(['live' => 0]);
    }

    /**
     * Handle the WebSocketDialog "restored" event.
     *
     * @param  \App\Models\WebSocketDialog  $webSocketDialog
     * @return void
     */
    public function restored(WebSocketDialog $webSocketDialog)
    {
        $userids = $this->userids($webSocketDialog);
        Deleted::forget('dialog', $webSocketDialog->id, $userids);
        WebSocketDialogMsgRead::whereDialogId($webSocketDialog->id)->whereIn('userid', $userids)->update(['live' => 1]);
    }

    /**
     * Handle the WebSocketDialog "force deleted" event.
     *
     * @param  \App\Models\WebSocketDialog  $webSocketDialog
     * @return void
     */
    public function forceDeleted(WebSocketDialog $webSocketDialog)
    {
        //
    }

    /**
     * @param WebSocketDialog $webSocketDialog
     * @return array
     */
    private function userids(WebSocketDialog $webSocketDialog)
    {
        return WebSocketDialogUser::whereDialogId($webSocketDialog->id)->pluck('userid')->toArray();
    }
}
