<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProjectTaskFlowChangesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('project_task_flow_changes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('task_id')->nullable()->default(0)->comment('任务ID');
            $table->bigInteger('userid')->nullable()->default(0)->comment('会员ID');
            $table->bigInteger('before_item_id')->nullable()->default(0)->comment('（变化前）工作流状态ID');
            $table->string('before_item_name', 50)->nullable()->default('')->comment('（变化前）工作流状态名称');
            $table->bigInteger('after_item_id')->nullable()->default(0)->comment('（变化后）工作流状态ID');
            $table->string('after_item_name', 50)->nullable()->default('')->comment('（变化后）工作流状态名称');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('project_task_flow_changes');
    }
}
