<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProjectsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name')->nullable()->default('')->comment('名称');
            $table->string('desc', 500)->nullable()->default('')->comment('描述、备注');
            $table->bigInteger('userid')->nullable()->default(0)->comment('创建人');
            $table->bigInteger('dialog_id')->nullable()->default(0)->comment('聊天会话ID');
            $table->timestamp('archived_at')->nullable()->comment('归档时间');
            $table->bigInteger('archived_userid')->nullable()->default(0)->comment('归档会员');
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
        Schema::dropIfExists('projects');
    }
}
