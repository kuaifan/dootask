<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddWebSocketDialogUsersColor extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::table('web_socket_dialog_users', function (Blueprint $table) {
            if (!Schema::hasColumn('web_socket_dialog_users', 'color')) {
                $table->string('color', 20)->nullable()->default('')->after('important')->comment('颜色');
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
        //
        Schema::table('web_socket_dialog_users', function (Blueprint $table) {
            $table->dropColumn("color");
        });
    }
}
