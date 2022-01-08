<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ProjectTasksAddFlowItemIdFlowItemName extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('project_tasks', function (Blueprint $table) {
            if (!Schema::hasColumn('project_tasks', 'flow_item_id')) {
                $table->bigInteger('flow_item_id')->nullable()->default(0)->after('dialog_id')->comment('工作流状态ID');
                $table->string('flow_item_name', 50)->nullable()->default('')->after('flow_item_id')->comment('工作流状态名称');
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
        Schema::table('project_tasks', function (Blueprint $table) {
            $table->dropColumn("flow_item_id");
            $table->dropColumn("flow_item_name");
        });
    }
}
