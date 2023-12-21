<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateOwnerAddIndexSome20231217 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // 项目相关
        Schema::table('projects', function (Blueprint $table) {
            $table->index('userid');
            $table->index('dialog_id');
        });
        Schema::table('project_users', function (Blueprint $table) {
            $table->index('userid');
            $table->index('project_id');
            $table->index(['project_id','userid']);
            $table->index('owner');
            $table->integer('owner')->change();
        });
        Schema::table('project_tasks', function (Blueprint $table) {
            $table->index('parent_id');
            $table->index('dialog_id');
            $table->index('userid');
            $table->integer('visibility')->change();
        });
        Schema::table('project_task_users', function (Blueprint $table) {
            $table->index(['task_id','userid']);
            $table->index('owner');
            $table->integer('owner')->change();
        });
        Schema::table('project_task_files', function (Blueprint $table) {
            $table->index('project_id');
            $table->index('task_id');
        });
        Schema::table('project_task_tags', function (Blueprint $table) {
            $table->index('project_id');
            $table->index('task_id');
        });
        Schema::table('project_task_contents', function (Blueprint $table) {
            $table->index('project_id');
            $table->index('task_id');
        });
        Schema::table('project_task_push_logs', function (Blueprint $table) {
            $table->index('userid');
            $table->index('task_id');
        });
        Schema::table('project_task_flow_changes', function (Blueprint $table) {
            $table->index('userid');
            $table->index('task_id');
        });

        // 聊天相关
        Schema::table('web_socket_dialogs', function (Blueprint $table) {
            $table->index('owner_id');
            $table->index('link_id');
        });
        Schema::table('web_socket_dialog_msgs', function (Blueprint $table) {
            $table->integer('link')->change();
            $table->integer('modify')->change();
            $table->integer('forward_show')->change();
        });
        Schema::table('web_socket_dialog_users', function (Blueprint $table) {
            $table->index('dialog_id');
            $table->index('userid');
            $table->integer('mark_unread')->change();
            $table->integer('silence')->change();
            $table->integer('important')->change();
        });
        Schema::table('web_socket_dialog_msg_todos', function (Blueprint $table) {
            $table->index('msg_id');
            $table->index('userid');
        });
        Schema::table('web_socket_dialog_msg_reads', function (Blueprint $table) {
            $table->index('dialog_id');
            $table->integer('mention')->change();
            $table->integer('silence')->change();
            $table->integer('email')->change();
            $table->integer('after')->change();
        });

        // 文件相关
        Schema::table('files', function (Blueprint $table) {
            $table->index('pid');
            $table->index('cid');
            $table->integer('share')->change();
        });
        Schema::table('file_users', function (Blueprint $table) {
            $table->index('file_id');
            $table->index('userid');
            $table->integer('permission')->change();
        });
        Schema::table('file_links', function (Blueprint $table) {
            $table->index('file_id');
            $table->index('userid');
        });
        Schema::table('file_contents', function (Blueprint $table) {
            $table->index('fid');
            $table->index('userid');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return voidw
     */
    public function down()
    {
        // 项目相关
        Schema::table('projects', function (Blueprint $table) {
            $table->dropIndex(['userid']);
            $table->dropIndex(['dialog_id']);
        });
        Schema::table('project_users', function (Blueprint $table) {
            $table->dropIndex(['userid']);
            $table->dropIndex(['project_id']);
            $table->dropIndex(['owner']);
            $table->dropIndex(['project_id','userid']);
        });
        Schema::table('project_tasks', function (Blueprint $table) {
            $table->dropIndex(['parent_id']);
            $table->dropIndex(['dialog_id']);
            $table->dropIndex(['userid']);
        });
        Schema::table('project_task_users', function (Blueprint $table) {
            $table->dropIndex(['owner']);
            $table->dropIndex(['task_id','userid']);
        });
        Schema::table('project_task_files', function (Blueprint $table) {
            $table->dropIndex(['project_id']);
            $table->dropIndex(['task_id']);
        });
        Schema::table('project_task_tags', function (Blueprint $table) {
            $table->dropIndex(['project_id']);
            $table->dropIndex(['task_id']);
        });
        Schema::table('project_task_contents', function (Blueprint $table) {
            $table->dropIndex(['project_id']);
            $table->dropIndex(['task_id']);
        });
        Schema::table('project_task_push_logs', function (Blueprint $table) {
            $table->dropIndex(['userid']);
            $table->dropIndex(['task_id']);
        });
        Schema::table('project_task_flow_changes', function (Blueprint $table) {
            $table->dropIndex(['userid']);
            $table->dropIndex(['task_id']);
        });

        // 聊天相关
        Schema::table('web_socket_dialogs', function (Blueprint $table) {
            $table->dropIndex(['owner_id']);
            $table->dropIndex(['link_id']);
        });
        Schema::table('web_socket_dialog_users', function (Blueprint $table) {
            $table->dropIndex(['dialog_id']);
            $table->dropIndex(['userid']);
        });
        Schema::table('web_socket_dialog_msg_todos', function (Blueprint $table) {
            $table->dropIndex(['msg_id']);
            $table->dropIndex(['userid']);
        });
        Schema::table('web_socket_dialog_msg_reads', function (Blueprint $table) {
            $table->dropIndex(['dialog_id']);
        });

        // 文件相关
        Schema::table('files', function (Blueprint $table) {
            $table->dropIndex(['pid']);
            $table->dropIndex(['cid']);
        });
        Schema::table('file_users', function (Blueprint $table) {
            $table->dropIndex(['file_id']);
            $table->dropIndex(['userid']);
        });
        Schema::table('file_links', function (Blueprint $table) {
            $table->dropIndex(['file_id']);
            $table->dropIndex(['userid']);
        });
        Schema::table('file_contents', function (Blueprint $table) {
            $table->dropIndex(['fid']);
            $table->dropIndex(['userid']);
        });
    }
}
