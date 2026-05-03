<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class BackfillDialogOwnerRole extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $prefix = DB::getTablePrefix();
        // 把每个群里 userid = web_socket_dialogs.owner_id 的成员记录设为 role=1（主群主）
        // 幂等：仅当 role=0 时才更新
        DB::statement("
            UPDATE {$prefix}web_socket_dialog_users du
            INNER JOIN {$prefix}web_socket_dialogs d ON d.id = du.dialog_id
            SET du.role = 1
            WHERE d.owner_id > 0
              AND du.userid = d.owner_id
              AND du.role = 0
        ");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $prefix = DB::getTablePrefix();
        // 回滚：把 role=1 的记录全部回到 role=0
        DB::statement("UPDATE {$prefix}web_socket_dialog_users SET role = 0 WHERE role = 1");
    }
}
