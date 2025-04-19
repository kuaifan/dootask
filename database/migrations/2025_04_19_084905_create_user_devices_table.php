<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserDevicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('user_devices'))
            return;

        Schema::create('user_devices', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('userid')->index()->nullable()->default(0)->comment('会员ID');
            $table->string('hash')->index()->nullable()->default('')->comment('TOKEN MD5');
            $table->longText('detail')->nullable()->comment('详细信息');
            $table->timestamp('expired_at')->nullable()->comment('过期时间');
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
        Schema::dropIfExists('user_devices');
    }
}
