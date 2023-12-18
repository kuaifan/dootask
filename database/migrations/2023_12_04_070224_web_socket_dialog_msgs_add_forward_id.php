<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class WebSocketDialogMsgsAddForwardId extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('web_socket_dialog_msgs', function (Blueprint $table) {
            if (!Schema::hasColumn('web_socket_dialog_msgs', 'forward_id')) {
                $table->bigInteger('forward_id')->nullable()->default(0)->after('reply_id')->comment('转发ID');
                $table->bigInteger('forward_num')->nullable()->default(0)->after('forward_id')->comment('被转发多少次');
                $table->boolean('forward_show')->nullable()->default(1)->after('forward_num')->comment('是否显示转发的来源');
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
        Schema::table('web_socket_dialog_msgs', function (Blueprint $table) {
            $table->dropColumn("forward_id");
            $table->dropColumn("forward_num");
            $table->dropColumn("forward_show");
        });
    }
}
