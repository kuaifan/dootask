<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUmengAliasDevice extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::table('umeng_alias', function (Blueprint $table) {
            if (!Schema::hasColumn('umeng_alias', 'device')) {
                $table->text('ua')->nullable()->after('platform')->comment('userAgent');
                $table->string('device', 100)->nullable()->default('')->after('platform')->comment('设备类型');
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
        Schema::table('umeng_alias', function (Blueprint $table) {
            $table->dropColumn("ua");
            $table->dropColumn("device");
        });
    }
}
