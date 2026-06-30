<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('files', function (Blueprint $table) {
            $table->char('hash', 32)->nullable()->after('size')->comment('文件内容 md5（分片上传秒传用）');
            $table->index('hash', 'files_hash_index');
        });
    }

    public function down()
    {
        Schema::table('files', function (Blueprint $table) {
            $table->dropIndex('files_hash_index');
            $table->dropColumn('hash');
        });
    }
};
