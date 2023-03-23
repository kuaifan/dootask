<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddWebSocketDialogMsgsLink extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $isAdd = false;
        Schema::table('web_socket_dialog_msgs', function (Blueprint $table) use (&$isAdd) {
            if (!Schema::hasColumn('web_socket_dialog_msgs', 'link')) {
                $isAdd = true;
                $table->boolean('link')->default(0)->after('tag')->nullable()->comment('是否存在链接');
            }
        });
        if ($isAdd) {
            DB::table('web_socket_dialog_msgs')->where('type', 'text')->where('msg', 'LIKE', '%<a %')->update([
                'link' => 1
            ]);
            DB::table('web_socket_dialog_msgs')->where('type', 'text')->where('msg', 'LIKE', '%http:%')->update([
                'link' => 1
            ]);
            DB::table('web_socket_dialog_msgs')->where('type', 'text')->where('msg', 'LIKE', '%https:%')->update([
                'link' => 1
            ]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('web_socket_dialog_msgs', function (Blueprint $table) {
            $table->dropColumn("link");
        });
    }
}
