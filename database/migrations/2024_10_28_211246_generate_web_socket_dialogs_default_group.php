<?php

use App\Models\User;
use App\Models\WebSocketDialog;
use App\Models\WebSocketDialogUser;
use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;

class GenerateWebSocketDialogsDefaultGroup extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (WebSocketDialog::count() > 0 && !WebSocketDialog::whereGroupType('all')->exists()) {
            $botUser = User::botGetOrCreate('bot-manager');
            if ($botUser) {
                $dialog = WebSocketDialog::checkUserDialog($botUser, 1);
                if ($dialog) {
                    WebSocketDialogUser::whereDialogId($dialog->id)->update(['last_at' => Carbon::now()]);
                }
            }

            $userids = User::whereBot(0)->whereNull('disable_at')->pluck('userid')->toArray();
            WebSocketDialog::createGroup("全体成员 All members", $userids, 'all');
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {

    }
}
