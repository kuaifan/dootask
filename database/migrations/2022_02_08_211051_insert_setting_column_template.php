<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class InsertSettingColumnTemplate extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $array = \App\Module\Base::setting('columnTemplate');
        if (empty($array)) {
            \App\Module\Base::setting('columnTemplate', [
                [
                    'name' => '软件开发',
                    'columns' => ['产品规划', '前端开发', '后端开发', '测试', '发布', '其他'],
                ],
                [
                    'name' => '产品开发',
                    'columns' => ['产品计划', '正在设计', '正在研发', '测试', '准备发布', '发布成功'],
                ],
            ]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {

    }
}
