<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

class repairProjectTasksIsAllVisible extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // 修复子任务可见性字段为null的数据
        if (Schema::hasTable('project_tasks')) {
            $prefix = DB::getConfig('prefix');
            DB::update("
                UPDATE {$prefix}project_tasks 
                SET is_all_visible = 1
                WHERE is_all_visible is null AND parent_id = 0;
            ");
            DB::update("
                UPDATE {$prefix}project_tasks t1
                JOIN {$prefix}project_tasks t2 ON t1.parent_id = t2.id
                SET t1.is_all_visible = t2.is_all_visible
                WHERE t1.is_all_visible is null AND t1.parent_id > 0;
            ");
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
