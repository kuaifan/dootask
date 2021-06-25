<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProjectTaskTagsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('project_task_tags', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('project_id')->nullable()->default(0)->comment('项目ID');
            $table->bigInteger('task_id')->nullable()->default(0)->comment('任务ID');
            $table->string('name', 50)->nullable()->default('')->comment('标题');
            $table->string('color', 10)->nullable()->default('')->comment('颜色');
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
        Schema::dropIfExists('project_task_tags');
    }
}
