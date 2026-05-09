<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class BackfillDialogRoleConsistency extends Migration
{
    /**
     * Run the migrations.
     *
     * 兜底修复 role/owner_id 一致性：
     * - 部门群 owner_id 与 user_departments.owner_userid 对齐
     * - owner_id > 0 的群确保 owner 成员存在且 role=1
     * - 同一群中非 owner 的 role=1 降为普通成员（不影响 role=2 管理员）
     * - 历史 owner_id=0 的普通用户群按最早的非机器人成员回填群主
     * - 清理部门负责人残留的 user_department_owners 记录
     *
     * 全部语句带幂等条件，可重复运行。
     *
     * @return void
     */
    public function up()
    {
        $prefix = DB::getTablePrefix();

        // 1) 部门群 owner_id 以 user_departments.owner_userid 为准
        DB::statement("\n            UPDATE {$prefix}web_socket_dialogs d\n            INNER JOIN {$prefix}user_departments ud ON ud.dialog_id = d.id\n            SET d.owner_id = ud.owner_userid\n            WHERE d.type = 'group'\n              AND d.group_type = 'department'\n              AND d.deleted_at IS NULL\n              AND ud.owner_userid > 0\n              AND d.owner_id != ud.owner_userid\n        ");

        // 2) 历史普通用户群 owner_id=0：按最早加入的非机器人成员回填群主
        DB::statement("\n            UPDATE {$prefix}web_socket_dialogs d\n            INNER JOIN (\n                SELECT du.dialog_id, MIN(du.id) AS min_id\n                FROM {$prefix}web_socket_dialog_users du\n                WHERE du.userid > 0 AND du.bot = 0\n                GROUP BY du.dialog_id\n            ) first_du ON first_du.dialog_id = d.id\n            INNER JOIN {$prefix}web_socket_dialog_users owner_du ON owner_du.id = first_du.min_id\n            SET d.owner_id = owner_du.userid\n            WHERE d.type = 'group'\n              AND d.group_type = 'user'\n              AND d.deleted_at IS NULL\n              AND d.owner_id = 0\n        ");

        // 3) owner_id > 0 但 owner 不在群成员表时，补一条成员记录（仅补真实存在的用户）
        DB::statement("\n            INSERT INTO {$prefix}web_socket_dialog_users\n                (dialog_id, userid, role, bot, important, last_at, created_at, updated_at)\n            SELECT\n                d.id,\n                d.owner_id,\n                1,\n                COALESCE(u.bot, 0),\n                CASE WHEN d.group_type IN ('user', 'all') THEN 0 ELSE 1 END,\n                CASE WHEN d.group_type IN ('user', 'department', 'all') THEN NOW(3) ELSE NULL END,\n                NOW(3),\n                NOW(3)\n            FROM {$prefix}web_socket_dialogs d\n            INNER JOIN {$prefix}users u ON u.userid = d.owner_id\n            LEFT JOIN {$prefix}web_socket_dialog_users du\n                ON du.dialog_id = d.id AND du.userid = d.owner_id\n            WHERE d.type = 'group'\n              AND d.deleted_at IS NULL\n              AND d.owner_id > 0\n              AND du.id IS NULL\n        ");

        // 4) owner 成员设为 role=1；业务群 owner 同时保持 important=1
        DB::statement("\n            UPDATE {$prefix}web_socket_dialog_users du\n            INNER JOIN {$prefix}web_socket_dialogs d ON d.id = du.dialog_id\n            SET du.role = 1,\n                du.important = CASE WHEN d.group_type IN ('user', 'all') THEN du.important ELSE 1 END\n            WHERE d.type = 'group'\n              AND d.deleted_at IS NULL\n              AND d.owner_id > 0\n              AND du.userid = d.owner_id\n              AND (du.role != 1 OR (d.group_type NOT IN ('user', 'all') AND du.important != 1))\n        ");

        // 5) 同一群里非 owner 的 role=1 降为普通成员，避免多个主群主
        DB::statement("\n            UPDATE {$prefix}web_socket_dialog_users du\n            INNER JOIN {$prefix}web_socket_dialogs d ON d.id = du.dialog_id\n            SET du.role = 0\n            WHERE d.type = 'group'\n              AND d.deleted_at IS NULL\n              AND d.owner_id > 0\n              AND du.role = 1\n              AND du.userid != d.owner_id\n        ");

        // 6) 部门负责人不能同时残留在部门管理员表
        DB::statement("\n            DELETE udo FROM {$prefix}user_department_owners udo\n            INNER JOIN {$prefix}user_departments ud ON ud.id = udo.department_id\n            WHERE ud.owner_userid = udo.userid\n        ");
    }

    /**
     * Reverse the migrations.
     *
     * 数据修复类迁移不提供精确回滚，避免破坏已校准的数据。
     *
     * @return void
     */
    public function down()
    {
        // no-op
    }
}
