<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class UsersChangeDisableAt extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $pre = DB::connection()->getTablePrefix();
        DB::statement("ALTER TABLE `{$pre}users` MODIFY COLUMN `disable_at` timestamp NULL DEFAULT NULL COMMENT '禁用时间（离职时间）' AFTER `created_ip`");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {

    }
}
