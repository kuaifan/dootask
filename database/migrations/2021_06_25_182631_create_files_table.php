<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('files', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('pid')->nullable()->default(0)->comment('上级ID');
            $table->bigInteger('cid')->nullable()->default(0)->comment('复制ID');
            $table->string('name', 100)->nullable()->default('')->comment('名称');
            $table->string('type', 20)->nullable()->default('')->comment('类型');
            $table->bigInteger('size')->nullable()->default(0)->comment('大小(B)');
            $table->bigInteger('userid')->nullable()->default(0)->comment('拥有者ID');
            $table->tinyInteger('share')->nullable()->default(0)->comment('是否共享');
            $table->bigInteger('created_id')->nullable()->default(0)->comment('创建者');
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
        Schema::dropIfExists('files');
    }
}
