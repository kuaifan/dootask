<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProjectTaskFilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('project_task_files', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('project_id')->nullable()->default(0)->comment('项目ID');
            $table->bigInteger('task_id')->nullable()->default(0)->comment('任务ID');
            $table->string('name', 100)->nullable()->default('')->comment('文件名称');
            $table->bigInteger('size')->nullable()->default(0)->comment('文件大小(B)');
            $table->string('ext', 20)->nullable()->default('')->comment('文件格式');
            $table->string('path')->nullable()->default('')->comment('文件地址');
            $table->string('thumb')->nullable()->default('')->comment('缩略图');
            $table->bigInteger('userid')->nullable()->default(0)->comment('上传用户ID');
            $table->integer('download')->nullable()->default(0)->comment('下载次数');
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
        Schema::dropIfExists('project_task_files');
    }
}
