<?php

use App\Models\User;
use App\Models\WebSocketDialog;
use Illuminate\Database\Migrations\Migration;

class GenerateWebSocketDialogsAllGroup extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (WebSocketDialog::count() > 0 && !WebSocketDialog::whereGroupType('all')->exists()) {
            $userids = User::whereNull('disable_at')->pluck('userid')->toArray();
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
