<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserCheckinMacsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('user_checkin_macs'))
            return;

        Schema::create('user_checkin_macs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('userid')->nullable()->default(0)->comment('会员id');
            $table->string('mac', 100)->nullable()->default('')->comment('MAC地址');
            $table->string('remark', 100)->nullable()->default('')->comment('备注');
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
        Schema::dropIfExists('user_checkin_macs');
    }
}
