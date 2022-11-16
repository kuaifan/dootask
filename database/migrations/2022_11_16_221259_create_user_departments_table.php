<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserDepartmentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_departments', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name', 100)->nullable()->default('')->comment('部门名称');
            $table->bigInteger('dialog_id')->nullable()->default(0)->comment('聊天会话ID');
            $table->bigInteger('parent_id')->nullable()->default(0)->comment('上级部门');
            $table->bigInteger('owner_userid')->nullable()->default(0)->comment('部门负责人');
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
        Schema::dropIfExists('user_departments');
    }
}
