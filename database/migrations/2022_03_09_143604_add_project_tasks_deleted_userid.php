<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddProjectTasksDeletedUserid extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('project_tasks', function (Blueprint $table) {
            if (!Schema::hasColumn('project_tasks', 'deleted_userid')) {
                $table->bigInteger('deleted_userid')->nullable()->default(0)->after('deleted_at')->comment('删除会员');
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
        Schema::table('project_tasks', function (Blueprint $table) {
            $table->dropColumn("deleted_userid");
        });
    }
}
