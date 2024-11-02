<?php

namespace App\Observers;

use App\Models\Deleted;
use App\Models\WebSocketDialogUser;
use Carbon\Carbon;

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
        if (!$webSocketDialogUser->last_at) {
            if (in_array($webSocketDialogUser->webSocketDialog?->group_type, ['user', 'department', 'all'])) {
                $webSocketDialogUser->last_at = Carbon::now();
                $webSocketDialogUser->save();
            } else {
                $item = WebSocketDialogUser::whereDialogId($webSocketDialogUser->dialog_id)->orderByDesc('last_at')->first();
                if ($item?->last_at) {
                    $webSocketDialogUser->last_at = $item->last_at;
                    $webSocketDialogUser->save();
                }
            }
        }
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
