<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUsersBot extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $isAdd = false;
        Schema::table('users', function (Blueprint $table) use (&$isAdd) {
            if (!Schema::hasColumn('users', 'bot')) {
                $isAdd = true;
                $table->tinyInteger('bot')->nullable()->default(0)->after('email_verity')->comment('是否机器人');
            }
        });
        if ($isAdd) {
            User::botGetOrCreate('bot-manager');
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn("bot");
        });
    }
}
