<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateWebSocketDialogUsersUpdatedAt extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('web_socket_dialog_users', function (Blueprint $table) {
            $pre = DB::connection()->getTablePrefix();
            DB::statement("ALTER TABLE `{$pre}web_socket_dialog_users` MODIFY COLUMN `created_at` timestamp(3) NULL DEFAULT NULL AFTER `important`");
            DB::statement("ALTER TABLE `{$pre}web_socket_dialog_users` MODIFY COLUMN `updated_at` timestamp(3) NULL DEFAULT NULL AFTER `created_at`");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('web_socket_dialog_users', function (Blueprint $table) {
            //
        });
    }
}
