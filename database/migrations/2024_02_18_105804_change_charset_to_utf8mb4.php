<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

class ChangeCharsetToUtf8mb4 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $pre = DB::connection()->getTablePrefix();
        $tables = [
            "approve_execution",
            "approve_execution_history",
            "approve_identitylink",
            "approve_identitylink_history",
            "approve_proc_inst",
            "approve_proc_inst_history",
            "approve_proc_msgs",
            "approve_procdef",
            "approve_procdef_history",
            "approve_task",
            "approve_task_history"
        ];
        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                Schema::getConnection()->getPdo()->exec("ALTER TABLE `{$pre}{$table}` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            }
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
