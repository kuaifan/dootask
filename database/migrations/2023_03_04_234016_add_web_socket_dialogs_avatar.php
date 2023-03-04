<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddWebSocketDialogsAvatar extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('web_socket_dialogs', function (Blueprint $table) {
            if (!Schema::hasColumn('web_socket_dialogs', 'avatar')) {
                $table->string('avatar', 255)->nullable()->default('')->after('name')->comment('头像（群）');
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
        Schema::table('web_socket_dialogs', function (Blueprint $table) {
            $table->dropColumn("avatar");
        });
    }
}
