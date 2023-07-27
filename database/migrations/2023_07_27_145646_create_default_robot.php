<?php

use App\Models\User;
use App\Models\WebSocketDialog;
use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;

class CreateDefaultRobot extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (WebSocketDialog::count() > 0) {
            User::botGetOrCreate('ai-openai');
            User::botGetOrCreate('ai-claude');
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
