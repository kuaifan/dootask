<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDeviceHashAndUserLangToUmengAliasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('umeng_alias', function (Blueprint $table) {
            if (!Schema::hasColumn('umeng_alias', 'device_hash')) {
                $table->string('device_hash')->index()->nullable()->after('device')->comment('设备哈希值，用于关联UserDevice表');
                $table->string('user_lang', 10)->nullable()->after('device_hash')->comment('用户语言');
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
        Schema::table('umeng_alias', function (Blueprint $table) {
            $table->dropColumn(['device_hash', 'user_lang']);
        });
    }
}
