<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserEmailVerificationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_email_verifications', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('userid')->nullable()->default(0)->comment('用户id');
            $table->string('code')->nullable()->default('')->comment('验证参数');
            $table->string('email')->nullable()->default('')->comment('电子邮箱');
            $table->integer('status')->nullable()->default(0)->comment('0-未验证，1-已验证');
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
        Schema::dropIfExists('user_email_verifications');
    }
}
