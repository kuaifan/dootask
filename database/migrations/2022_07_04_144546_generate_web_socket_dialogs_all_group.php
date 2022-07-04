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
        if (!WebSocketDialog::whereGroupType('all')->exists()) {
            $userids = User::whereNull('disable_at')->pluck('userid')->toArray();
            WebSocketDialog::createGroup(null, $userids, 'all');
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
