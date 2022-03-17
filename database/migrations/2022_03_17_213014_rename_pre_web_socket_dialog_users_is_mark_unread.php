<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RenamePreWebSocketDialogUsersIsMarkUnread extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('web_socket_dialog_users', function (Blueprint $table) {
            if (Schema::hasColumn('web_socket_dialog_users', 'is_mark_unread')) {
                $table->renameColumn('is_mark_unread', 'mark_unread');
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
        Schema::table('web_socket_dialog_users', function (Blueprint $table) {
            //
        });
    }
}
