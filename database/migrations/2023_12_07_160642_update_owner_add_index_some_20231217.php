<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateOwnerAddIndexSome20231217 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('project_users', function (Blueprint $table) {
            $table->index('userid');
            $table->index('project_id');
            $table->index(['project_id','userid']);
            //
            $table->index('owner');
            $table->integer('owner')->change();
        });
        Schema::table('project_tasks', function (Blueprint $table) {
            $table->index('parent_id');
            $table->index('dialog_id');
            $table->index('userid');
            //
            if (Schema::hasColumn('project_tasks', 'visibility')) {
                $table->integer('visibility')->change();
            }
        });
        Schema::table('project_task_users', function (Blueprint $table) {
            $table->index(['task_id','userid']);
            //
            $table->index('owner');
            $table->integer('owner')->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return voidw
     */
    public function down()
    {
        Schema::table('project_users', function (Blueprint $table) {
            $table->dropIndex(['userid']);
            $table->dropIndex(['project_id']);
            $table->dropIndex(['owner']);
            $table->dropIndex(['project_id','userid']);
        });
        Schema::table('project_tasks', function (Blueprint $table) {
            $table->dropIndex(['parent_id']);
            $table->dropIndex(['dialog_id']);
            $table->dropIndex(['userid']);
        });
        Schema::table('project_task_users', function (Blueprint $table) {
            $table->dropIndex(['owner']);
            $table->dropIndex(['task_id','userid']);
        });

    }
}
