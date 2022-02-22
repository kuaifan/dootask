<?php

use App\Models\File;
use Illuminate\Database\Migrations\Migration;

class FilesUpdateType extends Migration
{
    /**
     * 更改流程图文件类型
     * @return void
     */
    public function up()
    {
        File::whereType('flow')->update([
            'type' => 'drawio'
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        File::whereType('drawio')->update([
            'type' => 'flow'
        ]);
    }
}
