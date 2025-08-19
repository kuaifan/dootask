<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSortFieldToProjectUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('project_users', function (Blueprint $table) {
            // 添加一个排序sort字段
            if (!Schema::hasColumn('project_users', 'sort')) {
                $table->integer('sort')->nullable()->default(0)->after('top_at')->comment('排序(ASC)');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('project_users', function (Blueprint $table) {
            // 删除排序sort字段
            if (Schema::hasColumn('project_users', 'sort')) {
                $table->dropColumn('sort');
            }
        });
    }
}
