<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUserBotsClear extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $isAdd = false;
        Schema::table('user_bots', function (Blueprint $table) use (&$isAdd) {
            if (!Schema::hasColumn('user_bots', 'clear_day')) {
                $isAdd = true;
                $table->smallInteger('clear_day')->nullable()->default(90)->after('bot_id')->comment('消息自动清理天数');
                $table->timestamp('clear_at')->nullable()->after('clear_day')->comment('下一次清理时间');
            }
        });
        if ($isAdd) {
            \App\Models\UserBot::whereNull('clear_at')->update(['clear_at' => \Carbon\Carbon::now()->addDays(90)]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('user_bots', function (Blueprint $table) {
            $table->dropColumn("clear_day");
            $table->dropColumn("clear_at");
        });
    }
}
