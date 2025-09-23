<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserRecentItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('user_recent_items')) {
            return;
        }

        Schema::create('user_recent_items', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('userid')->index()->default(0)->comment('用户ID');
            $table->string('target_type', 50)->default('')->comment('目标类型(task/file/task_file/message_file 等)');
            $table->bigInteger('target_id')->default(0)->comment('目标ID');
            $table->string('source_type', 50)->default('')->comment('来源类型(project/filesystem/project_task/dialog 等)');
            $table->bigInteger('source_id')->default(0)->comment('来源ID');
            $table->timestamp('browsed_at')->nullable()->index()->comment('浏览时间');
            $table->timestamps();

            $table->index(['userid', 'browsed_at']);
            $table->unique(['userid', 'target_type', 'target_id', 'source_type', 'source_id'], 'recent_unique');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_recent_items');
    }
}
