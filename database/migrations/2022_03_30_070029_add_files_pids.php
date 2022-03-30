<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFilesPids extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $isAdd = false;
        Schema::table('files', function (Blueprint $table) use (&$isAdd) {
            if (!Schema::hasColumn('files', 'pids')) {
                $isAdd = true;
                $table->string('pids', 255)->nullable()->default('')->after('pid')->comment('上级ID递归');
            }
        });
        if ($isAdd) {
            // 更新数据
            \App\Models\File::where('pid', '>', 0)->chunkById(100, function ($lists) {
                /** @var \App\Models\File $item */
                foreach ($lists as $item) {
                    $item->saveBeforePids();
                }
            });
            \App\Models\File::whereShare(0)->chunkById(100, function ($lists) {
                /** @var \App\Models\File $item */
                foreach ($lists as $item) {
                    \App\Models\FileUser::whereFileId($item->id)->delete();
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
        Schema::table('files', function (Blueprint $table) {
            $table->dropColumn("pids");
        });
    }
}
