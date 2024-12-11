<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIsNotifiedToUmengAlias extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('umeng_alias', function (Blueprint $table) {
            if (!Schema::hasColumn('umeng_alias', 'is_notified')) {
                $table->tinyInteger('is_notified')->nullable()->default(0)->after('ua')->comment('通知权限');
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
            if (Schema::hasColumn('umeng_alias', 'is_notified')) {
                $table->dropColumn('is_notified');
            }
        });
    }
}
