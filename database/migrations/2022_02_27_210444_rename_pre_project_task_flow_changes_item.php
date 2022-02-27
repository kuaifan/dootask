<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RenamePreProjectTaskFlowChangesItem extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('project_task_flow_changes', function (Blueprint $table) {
            if (Schema::hasColumn('project_task_flow_changes', 'before_item_id')) {
                $table->renameColumn('before_item_id', 'before_flow_item_id');
                $table->renameColumn('before_item_name', 'before_flow_item_name');
                $table->renameColumn('after_item_id', 'after_flow_item_id');
                $table->renameColumn('after_item_name', 'after_flow_item_name');
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
        Schema::table('project_task_flow_changes', function (Blueprint $table) {
            //
        });
    }
}
