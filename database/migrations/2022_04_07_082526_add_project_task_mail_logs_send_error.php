<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddProjectTaskMailLogsSendError extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('project_task_mail_logs', function (Blueprint $table) {
            if (!Schema::hasColumn('project_task_mail_logs', 'send_error')) {
                $table->text('send_error')->nullable()->after('is_send')->comment('邮件发送错误详情');
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
        Schema::table('project_task_mail_logs', function (Blueprint $table) {
            $table->dropColumn("send_error");
        });
    }
}
