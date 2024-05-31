<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateComplaintsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('complaints'))
            return;

        Schema::create('complaints', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('dialog_id')->nullable()->default(0)->comment('对话ID');
            $table->bigInteger('userid')->nullable()->default(0)->comment('举报人id');
            $table->bigInteger('type')->nullable()->default(0)->comment('举报类型');
            $table->string('reason', 500)->nullable()->default('')->comment('举报原因');
            $table->text('imgs')->nullable()->comment('举报图片');
            $table->bigInteger('status')->nullable()->default(0)->comment('状态 0待处理、1已处理、2已删除');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('complaints');
    }
}
