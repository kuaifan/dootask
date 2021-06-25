<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProjectTaskContentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('project_task_contents', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('project_id')->nullable()->default(0)->comment('项目ID');
            $table->bigInteger('task_id')->nullable()->default(0)->comment('任务ID');
            $table->longText('content')->nullable()->comment('内容');
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
        Schema::dropIfExists('project_task_contents');
    }
}
