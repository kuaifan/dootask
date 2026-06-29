<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAppBadgesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('app_badges')) {
            return;
        }

        Schema::create('app_badges', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('app_id', 100)->default('')->comment('应用ID（appstore 插件 appid 或自定义微应用 id）');
            $table->string('menu_key', 100)->default('')->comment('菜单稳定标识；空串表示该应用的第一个菜单');
            $table->bigInteger('userid')->comment('用户ID');
            $table->integer('count')->default(0)->comment('角标数字');
            $table->boolean('dot')->default(false)->comment('是否显示红点');
            $table->timestamp('updated_at')->nullable()->comment('更新时间');
            //
            $table->unique(['app_id', 'menu_key', 'userid'], 'app_badges_unique');
            $table->index('userid', 'app_badges_userid');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('app_badges');
    }
}
