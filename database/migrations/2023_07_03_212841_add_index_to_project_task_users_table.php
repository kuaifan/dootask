<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIndexToProjectTaskUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('project_task_users', function (Blueprint $table) {
            if (Schema::hasColumn('project_task_users','userid')) {
                $table->index('userid');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return voidw
     */
    public function down()
    {
        Schema::table('project_task_users', function (Blueprint $table) {
            $table->dropIndex(['userid']);
        });
    }
}
