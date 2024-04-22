<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddProjectTaskContentsUserid extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::table('project_task_contents', function (Blueprint $table) {
            if (!Schema::hasColumn('project_task_contents', 'userid')) {
                $table->string('desc', 500)->nullable()->default('')->after('task_id')->comment('内容描述');
                $table->bigInteger('userid')->nullable()->default(0)->after('task_id')->comment('用户ID');
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
        //
        Schema::table('project_task_contents', function (Blueprint $table) {
            $table->dropColumn("desc");
            $table->dropColumn("userid");
        });
    }
}
