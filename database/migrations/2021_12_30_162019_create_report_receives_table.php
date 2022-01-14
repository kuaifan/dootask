<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReportReceivesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if ( Schema::hasTable('report_receives') )
            return;

        Schema::create('report_receives', function (Blueprint $table) {
            $table->bigIncrements("id");
            $table->unsignedInteger("rid")->default(0);
            $table->timestamp("receive_time")->nullable()->comment("接收时间");
            $table->unsignedBigInteger("userid")->default(0)->comment("接收人");
            $table->unsignedTinyInteger("read")->default(0)->comment("是否已读");
            $table->index(["userid", "receive_time"], "default");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('report_receives');
    }
}
