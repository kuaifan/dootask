<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIndexToProjectTasksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('project_tasks', function (Blueprint $table) {
            if (Schema::hasColumn('project_tasks','project_id')) {
                $table->index('project_id');
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
        Schema::table('project_tasks', function (Blueprint $table) {
            $table->dropIndex('project_id');
        });
    }
}
