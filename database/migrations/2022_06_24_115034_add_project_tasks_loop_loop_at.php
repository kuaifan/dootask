<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddProjectTasksLoopLoopAt extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('project_tasks', function (Blueprint $table) {
            if (!Schema::hasColumn('project_tasks', 'loop')) {
                $table->timestamp('loop_at')->nullable()->after('sort')->comment('下一次重复时间');
                $table->string('loop', 20)->nullable()->default('')->after('sort')->comment('重复周期');
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
            $table->dropColumn("loop_at");
            $table->dropColumn("loop");
        });
    }
}
