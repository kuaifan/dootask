<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddWebSocketDialogUsersAddHide extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('web_socket_dialog_users', function (Blueprint $table) {
            if (!Schema::hasColumn('web_socket_dialog_users', 'hide')) {
                $table->integer('hide')->nullable()->default(0)->after('silence')->comment('不显示会话：0否，1是');
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
            $table->dropColumn("hide");
        });
    }
}
