<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('project_tasks', function (Blueprint $table) {
            $table->index(
                ['project_id', 'parent_id', 'visibility', 'archived_at', 'deleted_at', 'complete_at', 'end_at'],
                'idx_pt_dashboard_due'
            );
            $table->index(
                ['project_id', 'parent_id', 'visibility', 'archived_at', 'deleted_at', 'complete_at', 'p_level'],
                'idx_pt_dashboard_priority'
            );
        });

        Schema::table('project_task_users', function (Blueprint $table) {
            $table->index(['task_id', 'owner', 'userid'], 'idx_ptu_task_owner_user');
            $table->index(['userid', 'owner', 'task_id'], 'idx_ptu_user_owner_task');
        });
    }

    public function down()
    {
        Schema::table('project_tasks', function (Blueprint $table) {
            $table->dropIndex('idx_pt_dashboard_due');
            $table->dropIndex('idx_pt_dashboard_priority');
        });

        Schema::table('project_task_users', function (Blueprint $table) {
            $table->dropIndex('idx_ptu_task_owner_user');
            $table->dropIndex('idx_ptu_user_owner_task');
        });
    }
};
