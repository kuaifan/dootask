<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProjectLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('project_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('project_id')->nullable()->default(0)->comment('项目ID');
            $table->bigInteger('column_id')->nullable()->default(0)->comment('列表ID');
            $table->bigInteger('task_id')->nullable()->default(0)->comment('任务ID');
            $table->bigInteger('userid')->nullable()->default(0)->comment('会员ID');
            $table->string('detail', 500)->nullable()->default('')->comment('详细信息');
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
        Schema::dropIfExists('project_logs');
    }
}
