<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserTaskBrowsesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('user_task_browses'))
            return;

        Schema::create('user_task_browses', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('userid')->index()->nullable()->default(0)->comment('用户ID');
            $table->bigInteger('task_id')->index()->nullable()->default(0)->comment('任务ID');
            $table->timestamp('browsed_at')->index()->nullable()->comment('浏览时间');
            $table->timestamps();

            // 复合索引：用户ID + 浏览时间（用于按时间排序获取用户浏览历史）
            $table->index(['userid', 'browsed_at']);
            // 唯一索引：用户ID + 任务ID（防止重复记录，相同任务会更新浏览时间）
            $table->unique(['userid', 'task_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_task_browses');
    }
}
