<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDeleteds extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('deleteds', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('type', 50)->nullable()->default('')->comment('删除的数据类型（如：project、task、dialog）');
            $table->bigInteger('did')->nullable()->default(0)->comment('删除的数据ID');
            $table->bigInteger('userid')->nullable()->default(0)->comment('关系会员ID');
            $table->timestamp('created_at')->nullable();
            $table->unique(['type', 'did', 'userid']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('deleted', function (Blueprint $table) {
            //
        });
    }
}
