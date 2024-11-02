<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIndexSome20241102 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('web_socket_dialog_msgs', function (Blueprint $table) {
            $table->index(['dialog_id', 'deleted_at']);
        });
        Schema::table('deleteds', function (Blueprint $table) {
            $table->index(['type', 'userid']);
            $table->index(['type', 'userid', 'deleted_at']);
        });
        Schema::table('report_receives', function (Blueprint $table) {
            $table->index(['userid', 'read', 'rid']);
        });
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
