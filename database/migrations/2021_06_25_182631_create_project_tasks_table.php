<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProjectTasksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('project_tasks', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->nullable()->default(0)->comment('父级任务ID');
            $table->bigInteger('project_id')->nullable()->default(0)->comment('项目ID');
            $table->bigInteger('column_id')->nullable()->default(0)->comment('列表ID');
            $table->bigInteger('dialog_id')->nullable()->default(0)->comment('聊天会话ID');
            $table->string('name')->nullable()->default('')->comment('标题');
            $table->string('color', 20)->nullable()->default('')->comment('颜色');
            $table->string('desc', 500)->nullable()->default('')->comment('描述');
            $table->timestamp('start_at')->nullable()->comment('计划开始时间');
            $table->timestamp('end_at')->nullable()->comment('计划结束时间');
            $table->timestamp('archived_at')->nullable()->comment('归档时间');
            $table->bigInteger('archived_userid')->nullable()->default(0)->comment('归档会员');
            $table->timestamp('complete_at')->nullable()->comment('完成时间');
            $table->bigInteger('userid')->nullable()->default(0)->comment('创建人');
            $table->tinyInteger('p_level')->nullable()->default(0)->comment('优先级');
            $table->string('p_name', 50)->nullable()->default('')->comment('优先级名称');
            $table->string('p_color', 20)->nullable()->default('')->comment('优先级颜色');
            $table->integer('sort')->nullable()->default(0)->comment('排序(ASC)');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('project_tasks');
    }
}
