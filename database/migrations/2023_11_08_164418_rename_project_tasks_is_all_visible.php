<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RenameProjectTasksIsAllVisible  extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('project_tasks', function (Blueprint $table) {
            if (Schema::hasColumn('project_tasks', 'is_all_visible')) {
                $pre = DB::connection()->getTablePrefix();
                DB::statement("ALTER TABLE `{$pre}project_tasks` MODIFY COLUMN is_all_visible TINYINT(1) DEFAULT 1 COMMENT '任务可见性：1-项目人员 2-任务人员 3-指定成员'");
                $table->renameColumn('is_all_visible', 'visibility');
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
    }
}
