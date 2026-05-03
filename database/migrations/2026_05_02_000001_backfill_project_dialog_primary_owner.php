<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class BackfillProjectDialogPrimaryOwner extends Migration
{
    /**
     * Run the migrations.
     *
     * 修复历史项目群聊未登记群主的问题：
     * - 早期 Project::addProject 调 createGroup 时未传 owner_id 第 4 参
     *   （在 commit 3a9001e09 才补上），导致老项目群 dialogs.owner_id = 0
     * - 这些群也因此被 2026_04_30_000002_backfill_dialog_owner_role 跳过
     *   （那条迁移要求 owner_id > 0），主负责人那条 dialog_users.role 仍为 0
     *
     * 本迁移仅处理 group_type = 'project' 且未软删的项目：
     *   (a) dialogs.owner_id = 0 → 按 project_users.owner=1 回填
     *   (b) 同一批群里主负责人那条 dialog_users.role = 0 → 设为 1
     *
     * 全部带幂等条件，可重跑。
     *
     * @return void
     */
    public function up()
    {
        $prefix = DB::getTablePrefix();

        // (a) 回填 dialogs.owner_id
        DB::statement("
            UPDATE {$prefix}web_socket_dialogs d
            INNER JOIN {$prefix}projects p ON p.dialog_id = d.id
            INNER JOIN {$prefix}project_users pu ON pu.project_id = p.id AND pu.owner = 1
            SET d.owner_id = pu.userid
            WHERE d.owner_id = 0
              AND d.group_type = 'project'
              AND p.deleted_at IS NULL
        ");

        // (b) 把这些项目群里主负责人那条 dialog_users.role 设为 1
        //     不依赖 (a) 的结果，直接按 project_users.owner=1 反查，幂等条件 du.role=0
        DB::statement("
            UPDATE {$prefix}web_socket_dialog_users du
            INNER JOIN {$prefix}projects p ON p.dialog_id = du.dialog_id
            INNER JOIN {$prefix}project_users pu
                ON pu.project_id = p.id
               AND pu.userid = du.userid
               AND pu.owner = 1
            SET du.role = 1
            WHERE du.role = 0
              AND p.deleted_at IS NULL
        ");
    }

    /**
     * Reverse the migrations.
     *
     * 数据回填类迁移不提供精确回滚——回滚会丢失原本就正确的数据。
     * 如需重置，请手动操作。
     *
     * @return void
     */
    public function down()
    {
        // no-op
    }
}
