<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserDepartmentOwnersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('user_department_owners')) {
            Schema::create('user_department_owners', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('department_id')->comment('部门ID');
                $table->unsignedBigInteger('userid')->comment('部门管理员 userid');
                $table->timestamp('created_at')->useCurrent();
                $table->unique(['department_id', 'userid'], 'uniq_dept_user');
                $table->index('userid', 'idx_userid');
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
        if (Schema::hasTable('user_department_owners')) {
            Schema::dropIfExists('user_department_owners');
        }
    }
}
