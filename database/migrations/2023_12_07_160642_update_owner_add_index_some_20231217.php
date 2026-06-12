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
            // Laravel 11+ 的 change() 会丢弃未声明的修饰符，须重申 nullable/default/comment
            $table->integer('owner')->nullable()->default(0)->comment('是否负责人')->change();
        });
        Schema::table('project_tasks', function (Blueprint $table) {
            $table->index('parent_id');
            $table->index('dialog_id');
            $table->index('userid');
            $table->integer('visibility')->nullable()->default(1)->comment('任务可见性：1-项目人员 2-任务人员 3-指定成员')->change();
        });
        Schema::table('project_task_users', function (Blueprint $table) {
            $table->index(['task_id','userid']);
            $table->index('owner');
            $table->integer('owner')->nullable()->default(0)->comment('是否任务负责人')->change();
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
            $table->integer('link')->nullable()->default(0)->comment('是否存在链接')->change();
            $table->integer('modify')->nullable()->default(0)->comment('是否编辑')->change();
            $table->integer('forward_show')->nullable()->default(1)->comment('是否显示转发的来源')->change();
        });
        Schema::table('web_socket_dialog_users', function (Blueprint $table) {
            $table->index('dialog_id');
            $table->index('userid');
            $table->integer('mark_unread')->nullable()->default(0)->comment('是否标记为未读：0否，1是')->change();
            $table->integer('silence')->nullable()->default(0)->comment('是否免打扰：0否，1是')->change();
            $table->integer('important')->nullable()->default(0)->comment('是否不可移出（项目、任务、部门人员）')->change();
        });
        Schema::table('web_socket_dialog_msg_todos', function (Blueprint $table) {
            $table->index('msg_id');
            $table->index('userid');
        });
        Schema::table('web_socket_dialog_msg_reads', function (Blueprint $table) {
            $table->index('dialog_id');
            $table->integer('mention')->nullable()->default(0)->comment('是否提及（被@）')->change();
            $table->integer('silence')->nullable()->default(0)->comment('是否免打扰：0否，1是')->change();
            $table->integer('email')->nullable()->default(0)->comment('是否发了邮件')->change();
            $table->integer('after')->nullable()->default(0)->comment('在阅读之后才添加的记录')->change();
        });

        // 文件相关
        Schema::table('files', function (Blueprint $table) {
            $table->index('pid');
            $table->index('cid');
            $table->integer('share')->nullable()->default(0)->comment('是否共享')->change();
        });
        Schema::table('file_users', function (Blueprint $table) {
            $table->index('file_id');
            $table->index('userid');
            $table->integer('permission')->nullable()->default(0)->comment('权限：0只读，1读写')->change();
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
     * @return void
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
