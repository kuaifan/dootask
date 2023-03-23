<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUserBotsWebhook extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_bots', function (Blueprint $table) {
            if (!Schema::hasColumn('user_bots', 'webhook_url')) {
                $table->string('webhook_url', 255)->nullable()->default('')->after('clear_at')->comment('消息webhook地址');
                $table->integer('webhook_num')->nullable()->default(0)->after('webhook_url')->comment('消息webhook请求次数');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('user_bots', function (Blueprint $table) {
            $table->dropColumn("webhook_url");
            $table->dropColumn("webhook_num");
        });
    }
}
