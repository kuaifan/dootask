<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserDeletesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_deletes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('userid')->nullable()->default(0)->comment('用户id');
            $table->string('email', 100)->nullable()->default('')->comment('邮箱帐号');
            $table->text('reason')->nullable()->comment('注销原因');
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
        Schema::dropIfExists('user_deletes');
    }
}
