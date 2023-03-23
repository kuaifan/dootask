<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserCheckinRecordsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('user_checkin_records'))
            return;

        Schema::create('user_checkin_records', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('userid')->nullable()->default(0)->comment('会员id');
            $table->string('mac', 100)->nullable()->default('')->comment('MAC地址');
            $table->bigInteger('time')->nullable()->default(0)->comment('上报的时间戳');
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
        Schema::dropIfExists('user_checkin_records');
    }
}
