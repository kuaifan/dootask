<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUmengAliasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('umeng_alias', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('userid')->nullable()->default(0)->comment('会员ID');
            $table->string('alias', 50)->nullable()->default('')->comment('别名');
            $table->string('platform', 50)->nullable()->default('')->comment('平台类型');
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
        Schema::dropIfExists('umeng_alias');
    }
}
