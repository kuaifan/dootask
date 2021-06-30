<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFileContentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('file_contents', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('fid')->nullable()->default(0)->comment('文件ID');
            $table->longText('content')->nullable()->comment('内容');
            $table->text('text')->nullable()->comment('内容（主要用于文档类型搜索）');
            $table->bigInteger('size')->nullable()->default(0)->comment('大小(B)');
            $table->bigInteger('userid')->nullable()->default(0)->comment('会员ID');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('file_contents');
    }
}
