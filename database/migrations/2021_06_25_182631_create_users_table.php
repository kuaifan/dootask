<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->bigIncrements('userid');
            $table->string('identity')->nullable()->default('')->comment('身份');
            $table->string('az', 10)->nullable()->default('')->comment('A-Z');
            $table->string('email', 100)->nullable()->default('')->unique()->comment('邮箱');
            $table->string('nickname')->nullable()->default('')->comment('昵称');
            $table->string('profession')->nullable()->default('')->comment('职位/职称');
            $table->string('userimg')->nullable()->default('')->comment('头像');
            $table->string('encrypt', 50)->nullable()->default('');
            $table->string('password', 50)->nullable()->default('')->comment('登录密码');
            $table->tinyInteger('changepass')->nullable()->default(0)->comment('登录需要修改密码');
            $table->integer('login_num')->nullable()->default(0)->comment('累计登录次数');
            $table->string('last_ip', 20)->nullable()->default('')->comment('最后登录IP');
            $table->timestamp('last_at')->nullable()->comment('最后登录时间');
            $table->string('line_ip', 20)->nullable()->default('')->comment('最后在线IP（接口）');
            $table->timestamp('line_at')->nullable()->comment('最后在线时间（接口）');
            $table->bigInteger('task_dialog_id')->nullable()->default(0)->comment('最后打开的任务会话ID');
            $table->string('created_ip', 20)->nullable()->default('')->comment('注册IP');
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
        Schema::dropIfExists('users');
    }
}
