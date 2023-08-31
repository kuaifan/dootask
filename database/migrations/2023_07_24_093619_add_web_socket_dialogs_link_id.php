<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddWebSocketDialogsLinkId extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::table('web_socket_dialogs', function (Blueprint $table) {
            if (!Schema::hasColumn('web_socket_dialogs', 'link_id')) {
                $table->bigInteger('link_id')->nullable()->default(0)->after('owner_id')->comment('关联id');
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
        Schema::table('web_socket_dialogs', function (Blueprint $table) {
            $table->dropColumn("link_id");
        });
    }
}
