<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddProjectTasksIsAllVisible extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::table('project_tasks', function (Blueprint $table) {
            if (!Schema::hasColumn('project_tasks', 'is_all_visible')) {
                $table->tinyInteger('is_all_visible')->nullable()->default(1)->after('userid')->comment('是否所有人可见');
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
        Schema::table('project_tasks', function (Blueprint $table) {
            $table->dropColumn("is_all_visible");
        });
    }
}
