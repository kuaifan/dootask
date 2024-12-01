<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProjectTaskTemplatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('project_task_templates')) {
            Schema::create('project_task_templates', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('project_id')->index()->comment('项目ID');
                $table->string('name', 100)->comment('模板名称');
                $table->string('title', 255)->nullable()->comment('任务标题');
                $table->text('content')->nullable()->comment('任务内容');
                $table->unsignedTinyInteger('sort')->default(0)->comment('排序');
                $table->boolean('is_default')->default(false)->comment('是否默认模板');
                $table->unsignedBigInteger('userid')->index()->comment('创建人');
                $table->timestamps();

                // 外键约束
                $table->foreign('project_id')->references('id')->on('projects')->onDelete('cascade');
                $table->foreign('userid')->references('userid')->on('users');
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
        Schema::dropIfExists('project_task_templates');
    }
}
