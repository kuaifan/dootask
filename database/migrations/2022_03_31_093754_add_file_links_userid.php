<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFileLinksUserid extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $isAdd = false;
        Schema::table('file_links', function (Blueprint $table) use (&$isAdd) {
            if (!Schema::hasColumn('file_links', 'userid')) {
                $isAdd = true;
                $table->bigInteger('userid')->nullable()->default(0)->after('code')->comment('会员ID');
            }
        });
        if ($isAdd) {
            // 更新数据
            \App\Models\FileLink::chunkById(100, function ($lists) {
                /** @var \App\Models\FileLink $item */
                foreach ($lists as $item) {
                    $item->userid = intval(\App\Models\File::whereId($item->file_id)->value('userid'));
                    $item->save();
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
        Schema::table('file_links', function (Blueprint $table) {
            $table->dropColumn("userid");
        });
    }
}
