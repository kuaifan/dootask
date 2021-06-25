<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProjectColumnsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('project_columns', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('project_id')->nullable()->default(0)->comment('项目ID');
            $table->string('name', 100)->nullable()->default('')->comment('列表名称');
            $table->string('color', 20)->nullable()->default('')->comment('颜色');
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
        Schema::dropIfExists('project_columns');
    }
}
