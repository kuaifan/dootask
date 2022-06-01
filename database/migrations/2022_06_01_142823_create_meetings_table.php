<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMeetingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('meetings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('meetingid')->nullable()->default('')->unique()->comment('会议ID，不是数字');
            $table->string('name')->nullable()->default('')->comment('会议主题');
            $table->string('channel')->nullable()->default('')->comment('频道');
            $table->bigInteger('userid')->nullable()->default(0)->comment('创建人');
            $table->timestamps();
            $table->timestamp('end_at')->nullable();
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
        Schema::dropIfExists('meetings');
    }
}
