<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddWebSocketDialogsOwnerId extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('web_socket_dialogs', function (Blueprint $table) {
            if (!Schema::hasColumn('web_socket_dialogs', 'owner_id')) {
                $table->bigInteger('owner_id')->nullable()->default(0)->after('last_at')->comment('群主用户ID');
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
            $table->dropColumn("owner_id");
        });
    }
}
