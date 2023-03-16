<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateTableIndexes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('web_socket_dialog_msg_reads', function (Blueprint $table) {
            $table->index(['userid', 'read_at', 'dialog_id']);
        });
        Schema::table('web_socket_dialog_msgs', function (Blueprint $table) {
            $table->index('dialog_id');
            $table->index('dialog_type');
            $table->index('userid');
        });
        Schema::table('task_workers', function (Blueprint $table) {
            $table->index('deleted_at');
        });
        Schema::table('project_tasks', function (Blueprint $table) {
            $table->index(['parent_id', 'deleted_at']);
        });
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
