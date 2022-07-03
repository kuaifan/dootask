<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddWebSocketDialogMsgsModify extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('web_socket_dialog_msgs', function (Blueprint $table) {
            if (!Schema::hasColumn('web_socket_dialog_msgs', 'modify')) {
                $table->boolean('modify')->default(0)->after('link')->nullable()->comment('是否编辑');
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
            $table->dropColumn("modify");
        });
    }
}
