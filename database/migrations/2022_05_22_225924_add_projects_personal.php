<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddProjectsPersonal extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $isAdd = false;
        Schema::table('projects', function (Blueprint $table) use (&$isAdd) {
            if (!Schema::hasColumn('projects', 'personal')) {
                $isAdd = true;
                $table->boolean('personal')->default(0)->after('userid')->nullable()->comment('是否个人项目');
            }
        });
        if ($isAdd) {
            // 更新数据
            \App\Models\Project::whereName('个人项目')->chunkById(100, function ($lists) {
                /** @var \App\Models\Project $item */
                foreach ($lists as $item) {
                    if ($item->desc == '注册时系统自动创建项目，你可以自由删除。') {
                        $item->personal = 1;
                        $item->save();
                    }
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn("personal");
        });
    }
}
