<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddGuestAccessToFilesTable extends Migration
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
            if (!Schema::hasColumn('files', 'guest_access')) {
                $table->tinyInteger('guest_access')->nullable()->default(0)->comment('是否允许游客访问')->after('share');
                $isAdd = true;
            }
        });
        if ($isAdd) {
            // 更新现有记录的guest_access字段为0（默认不允许游客访问）
            \DB::table('files')->whereNull('guest_access')->update(['guest_access' => 0]);
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
            if (Schema::hasColumn('files', 'guest_access')) {
                $table->dropColumn('guest_access');
            }
        });
    }
}
