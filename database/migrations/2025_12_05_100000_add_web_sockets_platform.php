<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddWebSocketsPlatform extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('web_sockets', function (Blueprint $table) {
            $table->string('platform', 20)->nullable()->default('')->after('path')->comment('平台类型：android, ios, win, mac, web');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('web_sockets', function (Blueprint $table) {
            $table->dropColumn('platform');
        });
    }
}

