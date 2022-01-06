<?php

use App\Models\File;
use App\Models\FileUser;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class FileUsersAddPermission extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $isAdd = false;
        Schema::table('file_users', function (Blueprint $table) use (&$isAdd) {
            if (!Schema::hasColumn('file_users', 'permission')) {
                $isAdd = true;
                $table->tinyInteger('permission')->nullable()->default(0)->after('userid')->comment('权限：0只读，1读写');
            }
        });
        if ($isAdd) {
            // 更新数据
            File::whereShare(1)->chunkById(100, function ($lists) {
                foreach ($lists as $file) {
                    FileUser::updateInsert([
                        'file_id' => $file->id,
                        'userid' => 0,
                    ]);
                }
            });
            File::whereShare(2)->update([
                'share' => 1,
            ]);
            FileUser::wherePermission(0)->update([
                'permission' => 1,
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
        Schema::table('file_users', function (Blueprint $table) {
            $table->dropColumn("permission");
        });
    }
}
