<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRoleToWebSocketDialogUsers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('web_socket_dialog_users', function (Blueprint $table) {
            if (!Schema::hasColumn('web_socket_dialog_users', 'role')) {
                $table->tinyInteger('role')->default(0)->after('userid')
                    ->comment('0=普通成员 1=群主 2=群管理员');
                $table->index(['dialog_id', 'role'], 'idx_dialog_role');
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
        Schema::table('web_socket_dialog_users', function (Blueprint $table) {
            if (Schema::hasColumn('web_socket_dialog_users', 'role')) {
                $table->dropIndex('idx_dialog_role');
                $table->dropColumn('role');
            }
        });
    }
}
