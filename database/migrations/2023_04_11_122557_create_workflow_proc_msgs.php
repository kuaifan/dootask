<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWorkflowProcMsgs extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('workflow_proc_msgs')) {
            Schema::create('workflow_proc_msgs', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->bigInteger('proc_inst_id')->nullable()->default(0)->comment('流程实例ID');
                $table->bigInteger('userid')->nullable()->default(0)->comment('会员ID');
                $table->bigInteger('msg_id')->nullable()->default(0)->comment('消息ID');
                $table->timestamps();
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
        Schema::table('workflow_proc_msgs', function (Blueprint $table) {
            Schema::dropIfExists('workflow_proc_msgs');
        });
    }
}
