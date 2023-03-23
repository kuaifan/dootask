<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddWebSocketDialogUsersInviterImportant extends Migration
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
            if (!Schema::hasColumn('web_socket_dialog_users', 'important')) {
                $isAdd = true;
                $table->boolean('important')->default(0)->after('mark_unread')->nullable()->comment('是否不可移出（项目、任务、部门人员）');
                $table->bigInteger('inviter')->nullable()->default(0)->after('mark_unread')->comment('邀请人');
            }
        });
        if ($isAdd) {
            \App\Models\WebSocketDialog::whereIn('group_type', ['project', 'task'])->chunkById(100, function ($lists) {
                /** @var \App\Models\WebSocketDialog $item */
                foreach ($lists as $item) {
                    \App\Models\WebSocketDialogUser::whereDialogId($item->id)->change([
                        'important' => 1,
                    ]);
                }
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
        Schema::table('web_socket_dialog_users', function (Blueprint $table) {
            $table->dropColumn("important");
            $table->dropColumn("inviter");
        });
    }
}
