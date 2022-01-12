<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProjectFlowItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('project_flow_items', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('project_id')->nullable()->default(0)->comment('项目ID');
            $table->bigInteger('flow_id')->nullable()->default(0)->comment('流程ID');
            $table->string('name', 50)->nullable()->default('')->comment('名称');
            $table->string('status', 20)->nullable()->default('')->comment('状态');
            $table->string('turns')->nullable()->default('')->comment('可流转');
            $table->string('userids')->nullable()->default('')->comment('状态负责人ID');
            $table->integer('sort')->nullable()->comment('排序');
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
        Schema::dropIfExists('project_flow_items');
    }
}
