<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class FilesAddExt extends Migration
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
            if (!Schema::hasColumn('files', 'ext')) {
                $isAdd = true;
                $table->string('ext', 20)->nullable()->default('')->after('type')->comment('后缀名');
            }
        });
        if ($isAdd) {
            // 更新数据
            \App\Models\File::chunkById(100, function ($lists) {
                foreach ($lists as $item) {
                    if (in_array($item->type, ['word', 'excel', 'ppt'])) {
                        $item->ext = str_replace(['word', 'excel', 'ppt'], ['docx', 'xlsx', 'pptx'], $item->type);
                        $item->save();
                    }
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
            $table->dropColumn("ext");
        });
    }
}
