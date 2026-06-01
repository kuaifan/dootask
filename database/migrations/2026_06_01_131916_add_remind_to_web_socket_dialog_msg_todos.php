<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRemindToWebSocketDialogMsgTodos extends Migration
{
    public function up()
    {
        Schema::table('web_socket_dialog_msg_todos', function (Blueprint $table) {
            $table->timestamp('remind_at')->nullable()->comment('提醒时间')->after('done_at');
            $table->timestamp('reminded_at')->nullable()->comment('已提醒时间')->after('remind_at');
            $table->index(['remind_at', 'reminded_at', 'done_at'], 'idx_todo_remind');
        });
    }

    public function down()
    {
        Schema::table('web_socket_dialog_msg_todos', function (Blueprint $table) {
            $table->dropIndex('idx_todo_remind');
            $table->dropColumn(['remind_at', 'reminded_at']);
        });
    }
}
