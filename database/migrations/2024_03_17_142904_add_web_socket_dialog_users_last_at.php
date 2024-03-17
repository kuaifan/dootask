<?php

use App\Models\WebSocketDialog;
use App\Models\WebSocketDialogUser;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddWebSocketDialogUsersLastAt extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $isAdd = false;
        Schema::table('web_socket_dialog_users', function (Blueprint $table) use (&$isAdd) {
            if (!Schema::hasColumn('web_socket_dialog_users', 'last_at')) {
                $isAdd = true;
                $table->timestamp('last_at')->nullable()->after('top_at')->comment('最后消息时间');
            }
        });
        if ($isAdd) {
            // 更新数据
            WebSocketDialog::chunk(100, function ($dialogs) {
                /** @var WebSocketDialog $dialog */
                foreach ($dialogs as $dialog) {
                    WebSocketDialogUser::whereDialogId($dialog->id)->update(['last_at' => $dialog->last_at]);
                }
            });

            // 删除表字段
            Schema::table('web_socket_dialogs', function (Blueprint $table) {
                $table->dropColumn("last_at");
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // 回滚数据 - 无法回滚
    }
}
