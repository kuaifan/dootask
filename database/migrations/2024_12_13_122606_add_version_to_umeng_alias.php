<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddVersionToUmengAlias extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('umeng_alias', function (Blueprint $table) {
            if (!Schema::hasColumn('umeng_alias', 'version')) {
                $table->string('version', 50)->nullable()->default('')->after('device')->comment('应用版本号');
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
            if (Schema::hasColumn('umeng_alias', 'version')) {
                $table->dropColumn('version');
            }
        });
    }
}
