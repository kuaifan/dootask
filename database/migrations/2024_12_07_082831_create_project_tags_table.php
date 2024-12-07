<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProjectTagsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('project_tags')) {
            return;
        }
        Schema::create('project_tags', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('project_id')->index()->comment('项目ID');
            $table->string('name', 50)->comment('标签名称');
            $table->string('desc', 255)->nullable()->comment('标签描述');
            $table->string('color', 20)->nullable()->default('')->comment('颜色');
            $table->unsignedBigInteger('userid')->index()->comment('创建人');
            $table->timestamps();

            // 外键约束
            $table->foreign('project_id')->references('id')->on('projects')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('project_tags');
    }
}
