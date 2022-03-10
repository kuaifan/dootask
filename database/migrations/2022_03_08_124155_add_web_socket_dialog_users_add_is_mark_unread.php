<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddWebSocketDialogUsersAddIsMarkUnread extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('web_socket_dialog_users', function (Blueprint $table) {
            $table->boolean('is_mark_unread')->default(0)->nullable(false)->after('top_at')->comment('是否标记为未读：0否，1是');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('web_socket_dialog_users', function (Blueprint $table) {
            $table->dropColumn("is_mark_unread");
        });
    }
}
